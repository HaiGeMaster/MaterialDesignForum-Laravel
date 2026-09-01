<?php

/**
 * PHP 中文字符串扫描 / 国际化提取脚本
 *
 * 用法（在项目根目录执行）：
 *   php scripts/extract_i18n.php                  # 预览模式：扫描并生成 lang 文件，不修改源码
 *   php scripts/extract_i18n.php --apply          # 应用模式：生成 lang 文件，并将源码中的中文字符串替换为 __('...')
 *   php scripts/extract_i18n.php --dirs=app,routes# 自定义扫描目录（默认 app,routes）
 *   php scripts/extract_i18n.php --file=messages  # lang 文件名（默认 messages，生成 lang/{zh,en}/messages.php）
 *   php scripts/extract_i18n.php --exclude=vendor # 追加排除路径片段
 *
 * 说明：
 *  - 基于 PHP token 解析，只提取「字符串字面量」中的中文，注释/文档块/heredoc 均跳过；
 *  - 双引号字符串中的变量插值会转换为 Laravel 翻译占位符（:param）；
 *  - 同一内容的字符串全局去重，复用同一个 key；
 *  - lang/zh 写入中文原文，lang/en 以中文原文占位（头部注释提示人工翻译），已有翻译不会被覆盖；
 *  - --apply 前会自动备份被修改的文件到 storage/app/i18n-backup/<时间戳>/。
 */

$root = realpath(__DIR__ . '/..');

/* ---------- 参数解析 ---------- */

$options = [
    'apply'   => false,
    'dirs'    => ['app', 'routes'],
    'file'    => 'messages',
    'excludes'=> [
        'vendor', 'node_modules', '.git', '.codebuddy', '.playwright-cli',
        'storage', 'bootstrap', 'lang', 'public', 'tests', 'resources/js',
    ],
];

for ($i = 1; $i < count($argv); $i++) {
    $arg = $argv[$i];
    if ($arg === '--apply') {
        $options['apply'] = true;
    } elseif (str_starts_with($arg, '--dirs=')) {
        $options['dirs'] = array_filter(array_map('trim', explode(',', substr($arg, 7))));
    } elseif (str_starts_with($arg, '--file=')) {
        $options['file'] = basename(trim(substr($arg, 7)));
    } elseif (str_starts_with($arg, '--exclude=')) {
        $options['excludes'] = array_merge($options['excludes'], array_map('trim', explode(',', substr($arg, 9))));
    } else {
        fwrite(STDERR, "未知参数：{$arg}\n");
        exit(1);
    }
}

if (empty($options['dirs'])) {
    fwrite(STDERR, "扫描目录不能为空。\n");
    exit(1);
}

/* ---------- 文件收集 ---------- */

/** 判断路径是否应排除 */
function isExcluded(string $path, array $excludes): bool
{
    $normalized = str_replace('\\', '/', $path);
    foreach ($excludes as $ex) {
        $ex = trim($ex, '/');
        if ($ex === '') {
            continue;
        }
        if (str_contains('/' . $normalized . '/', '/' . $ex . '/')) {
            return true;
        }
    }
    return false;
}

/** 递归收集目录下所有 .php 文件 */
function collectPhpFiles(string $dir, array $excludes): array
{
    $files = [];
    if (! is_dir($dir)) {
        return $files;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        if (isExcluded($path, $excludes)) {
            continue;
        }
        $files[] = $path;
    }
    sort($files);
    return $files;
}

/* ---------- 字符串解析 ---------- */

/**
 * 反转义双引号字符串内容。
 */
function unescapeDoubleQuoted(string $s): string
{
    $out = '';
    $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $c = $s[$i];
        if ($c !== '\\') {
            $out .= $c;
            continue;
        }
        if (++$i >= $len) {
            $out .= '\\';
            break;
        }
        $n = $s[$i];
        switch ($n) {
            case 'n':  $out .= "\n"; break;
            case 'r':  $out .= "\r"; break;
            case 't':  $out .= "\t"; break;
            case 'v':  $out .= "\v"; break;
            case 'e':  $out .= "\e"; break;
            case 'f':  $out .= "\f"; break;
            case '\\': $out .= '\\'; break;
            case '$':  $out .= '$';  break;
            case '"':  $out .= '"';  break;
            case '{':  $out .= '{';  break;
            case 'x':
                if ($i + 2 < $len && ctype_xdigit($s[$i + 1]) && ctype_xdigit($s[$i + 2])) {
                    $out .= chr(hexdec(substr($s, $i + 1, 2)));
                    $i += 2;
                } else {
                    $out .= 'x';
                }
                break;
            default:
                // 八进制 \0 到 \7（最多 3 位）
                if ($n >= '0' && $n <= '7') {
                    $oct = $n;
                    for ($k = 0; $k < 2 && $i + 1 < $len && $s[$i + 1] >= '0' && $s[$i + 1] <= '7'; $k++) {
                        $oct .= $s[++$i];
                    }
                    $out .= chr(octdec($oct));
                } else {
                    // PHP 对未知转义序列保留反斜杠
                    $out .= '\\' . $n;
                }
        }
    }
    return $out;
}

/**
 * 反转义单引号字符串内容。
 */
function unescapeSingleQuoted(string $s): string
{
    return str_replace(['\\\\', "\\'"], ['\\', "'"], $s);
}

/**
 * 从字符串文本中解析真实值。
 * 返回 [value, params]；params 为 [占位名 => 表达式]（按出现顺序去重保留）。
 */
function parseStringToken(string $raw, array $siblingTokens = [], int $siblingStart = 0): array
{
    $quote = $raw[0] ?? '';

    // 单引号字符串（token 完整，无插值）
    if ($quote === "'") {
        return [unescapeSingleQuoted(substr($raw, 1, -1)), []];
    }

    // 双引号字符串：可能由多个 token 组成（含插值）
    $value = '';
    $params = [];
    $segments = array_merge([['type' => 'text', 'text' => substr($raw, 1, -1)]], $siblingTokens, [['type' => 'end']]);

    foreach ($segments as $seg) {
        $type = $seg['type'];
        if ($type === 'end') {
            break;
        }
        if ($type === 'text') {
            $value .= unescapeDoubleQuoted($seg['text']);
        } elseif ($type === 'var') {
            $name = $seg['name'];
            $value .= ':' . $name;
            $params[$name] = $seg['expr'];
        } elseif ($type === 'curly') {
            $name = $seg['name'] ?? 'param';
            $value .= ':' . $name;
            $params[$name] = $seg['expr'];
        }
    }

    return [$value, $params];
}

/**
 * 判断字符串是否包含中文（CJK 统一表意文字）。
 */
function containsChinese(string $s): bool
{
    return (bool) preg_match('/[\x{4e00}-\x{9fff}]/u', $s);
}

/* ---------- key 生成 ---------- */

/** 将相对路径转换为可读的 key 前缀 */
function keyPrefixFor(string $relPath): string
{
    $parts = explode('/', str_replace('\\', '/', $relPath));
    $base  = strtolower(preg_replace('/\.php$/', '', end($parts)));
    $base  = preg_replace('/_?controller$/', '', $base);

    // 顶层 app/ 下的第一级子目录
    if (($parts[0] ?? '') === 'app' && isset($parts[1])) {
        $dir = strtolower($parts[1]);
        $dirMap = [
            'http'       => 'http',
            'models'     => 'model',
            'services'   => 'service',
            'support'    => 'support',
            'mail'       => 'mail',
            'middleware' => 'middleware',
            'providers'  => 'provider',
            'exceptions' => 'exception',
        ];
        $prefix = ($dirMap[$dir] ?? $dir) . '_' . $base;
    } elseif (($parts[0] ?? '') === 'routes') {
        $prefix = 'route_' . $base;
    } else {
        $prefix = $base;
    }

    return preg_replace('/[^a-z0-9_]+/', '_', $prefix);
}

/* ---------- 主流程 ---------- */

$allFiles = [];
foreach ($options['dirs'] as $dir) {
    $allFiles = array_merge($allFiles, collectPhpFiles($root . DIRECTORY_SEPARATOR . $dir, $options['excludes']));
}
$allFiles = array_values(array_unique($allFiles));

if (empty($allFiles)) {
    echo "未找到可扫描的 PHP 文件。\n";
    exit(0);
}

$zhEntries = [];   // key => value
$enEntries = [];   // key => value
$sources   = [];   // key => [file:line, ...]
$valueToKey = [];  // 规范化值 => key
$seqByPrefix = [];
$stats = ['extracted' => 0, 'skipped' => 0, 'heredoc' => 0];
$pendingReplacements = []; // 文件 => [ [start, end, replacement], ... ]

/* 加载现有 lang 文件，建立「值 → key」映射，保证跨运行 key 稳定 */
$langFile = $options['file'];
$zhPath = $root . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'zh' . DIRECTORY_SEPARATOR . $langFile . '.php';
$enPath = $root . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . $langFile . '.php';

$existingValueToKey = []; // 值 => 已有 key
if (is_file($zhPath)) {
    $existingLang = include $zhPath;
    if (is_array($existingLang)) {
        foreach ($existingLang as $k => $v) {
            if (is_string($v)) {
                $existingValueToKey[$v] = $k;
            }
            // 初始化各前缀的已有最大序号，新 key 不会与旧 key 冲突
            if (is_string($k) && preg_match('/^(.+)_(\d+)$/', $k, $m)) {
                $seqByPrefix[$m[1]] = max($seqByPrefix[$m[1]] ?? 0, (int) $m[2]);
            }
        }
    }
}

/** 获取（或分配）一个 key */
function entryKeyFor(string $value, string $prefix, string $source): string
{
    global $valueToKey, $existingValueToKey, $seqByPrefix, $zhEntries, $enEntries, $sources;
    $normalized = $value;
    if (isset($valueToKey[$normalized])) {
        $key = $valueToKey[$normalized];
        $sources[$key][] = $source;
        return $key;
    }
    // 复用现有 lang 文件中相同内容的 key，保持跨运行稳定
    if (isset($existingValueToKey[$normalized])) {
        $key = $existingValueToKey[$normalized];
        $valueToKey[$normalized] = $key;
        $sources[$key][] = $source;
        return $key;
    }
    $seq = ($seqByPrefix[$prefix] ?? 0) + 1;
    $seqByPrefix[$prefix] = $seq;
    $key = "{$prefix}_{$seq}";
    $valueToKey[$normalized] = $key;
    $zhEntries[$key] = $value;
    $enEntries[$key] = $value; // en 暂以中文占位，等待人工翻译
    $sources[$key] = [$source];
    return $key;
}

/** 前一个非空白 token（返回原始 token 值） */
function prevSignificant(&$tokens, int $idx)
{
    for ($i = $idx - 1; $i >= 0; $i--) {
        $t = $tokens[$i];
        if (is_string($t)) {
            if (trim($t) === '') continue;
            return $t;
        }
        if (in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) continue;
        return $t;
    }
    return null;
}

/** 后一个非空白 token */
function nextSignificant(&$tokens, int $idx)
{
    $n = count($tokens);
    for ($i = $idx + 1; $i < $n; $i++) {
        $t = $tokens[$i];
        if (is_string($t)) {
            if (trim($t) === '') continue;
            return $t;
        }
        if (in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) continue;
        return $t;
    }
    return null;
}

/** 判断字符串位置是否安全（避免替换数组 key、case 值、比较操作数等） */
function isSafePosition(&$tokens, int $beforeIdx, int $afterIdx): bool
{
    $prev = prevSignificant($tokens, $beforeIdx);
    $next = nextSignificant($tokens, $afterIdx);

    // 作为数组 key / match key：后一个是 =>
    if ($next === '=>') return false;
    if (is_array($next) && in_array($next[0], [T_DOUBLE_ARROW])) return false;

    // switch case 的值 / 比较操作数
    if (is_array($prev) && $prev[0] === T_CASE) return false;
    if ($next === ':' ) return false; // 三元条件 ? a : '中文' 的 '中文' 后跟 : 是值位置，其实安全，但 key 场景已覆盖，这里保守跳过 "后跟 :"（switch case 冒号）
    if (in_array($prev, ['==', '!=', '===', '!==', '<>'])) return false;
    if (is_array($prev) && in_array($prev[0], [T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL])) return false;

    // 数组访问下标 $arr['中文']：前一个非空白是 [，且 [ 之前是表达式（变量 / 数组 / 括号 / 静态引用等）
    // 注意：数组字面量元素 ['中文', ...] / 'key' => ['中文'] 的 [ 前是 =、=>、(、, 等，不算数组访问
    if ($prev === '[') {
        $prev2 = prevSignificant($tokens, $beforeIdx - 1);
        if (is_array($prev2) && in_array($prev2[0], [T_VARIABLE, T_STRING, T_ARRAY, T_STATIC])) return false;
        if ($prev2 === ']' || $prev2 === ')' || $prev2 === '[') return false;
    }

    return true;
}

/**
 * 判断字符串是否位于「类常量 / 属性默认值 / 静态局部变量 / 枚举 case」等声明语句中。
 * PHP 中这些位置的初始值必须是常量表达式，不能调用 __() 函数，否则语法错误。
 */
function isInDeclarationStatement(&$tokens, int $idx): bool
{
    for ($i = $idx - 1; $i >= 0; $i--) {
        $t = $tokens[$i];
        if (is_string($t)) {
            if (trim($t) === '') continue;
            if ($t === ';' || $t === '{' || $t === '}') break;
            continue;
        }
        $id = $t[0];
        if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) continue;

        // 类常量 / 枚举 case
        if ($id === T_CONST || $id === T_CASE) return true;

        // 属性默认值 / 构造器提升参数：visibility 出现在语句段内（PHP 8 类型化属性同理）
        if (in_array($id, [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR])) return true;

        // 函数内静态局部变量：static $x = '中文'
        if ($id === T_STATIC) {
            $k = $i + 1;
            while ($k < $idx) {
                $tk = $tokens[$k];
                if (is_string($tk)) {
                    if (trim($tk) === '') { $k++; continue; }
                    break;
                }
                if (in_array($tk[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) { $k++; continue; }
                if ($tk[0] === T_VARIABLE) return true;
                break;
            }
        }
    }
    return false;
}

foreach ($allFiles as $file) {
    $code = file_get_contents($file);
    if ($code === false) continue;

    $tokens = token_get_all($code);
    $rel = ltrim(str_replace('\\', '/', substr($file, strlen($root))), '/');
    $prefix = keyPrefixFor($rel);
    $repl = [];

    $i = 0;
    $n = count($tokens);

    while ($i < $n) {
        $t = $tokens[$i];

        if (is_string($t)) {
            // 双引号字符串起始（含插值时 " 是独立 token；无插值双引号是 T_CONSTANT_ENCAPSED_STRING）
            if ($t === '"') {
                // 收集到闭合 " 之间的段
                $segments = [];
                $startIdx = $i;
                $startOffset = offsetAt($tokens, $i);
                $j = $i + 1;
                $hasInterp = false;
                while ($j < $n) {
                    $tj = $tokens[$j];
                    if ($tj === '"') {
                        break;
                    }
                    if (is_string($tj)) {
                        $segments[] = ['type' => 'text', 'text' => $tj];
                    } else {
                        $id = $tj[0];
                        $txt = $tj[1];
                        if ($id === T_ENCAPSED_AND_WHITESPACE) {
                            $segments[] = ['type' => 'text', 'text' => $txt];
                        } elseif ($id === T_VARIABLE) {
                            $name = substr($txt, 1);
                            $segments[] = ['type' => 'var', 'name' => $name, 'expr' => $txt];
                            $hasInterp = true;
                        } elseif ($id === T_CURLY_OPEN) {
                            // { ... } 内表达式
                            $k = $j + 1;
                            $expr = '';
                            $name = null;
                            while ($k < $n && $tokens[$k] !== '}') {
                                $tk = $tokens[$k];
                                $expr .= is_string($tk) ? $tk : $tk[1];
                                if ($name === null && is_array($tk) && $tk[0] === T_VARIABLE) {
                                    $name = substr($tk[1], 1);
                                }
                                $k++;
                            }
                            $segments[] = ['type' => 'curly', 'name' => $name ?? 'param', 'expr' => $expr];
                            $hasInterp = true;
                            $j = $k;
                        } else {
                            $segments[] = ['type' => 'text', 'text' => $txt];
                        }
                    }
                    $j++;
                }
                $endIdx = $j; // 闭合 " 的位置（若存在）
                $endOffset = ($endIdx < $n && $tokens[$endIdx] === '"')
                    ? offsetAt($tokens, $endIdx) + 1
                    : $startOffset + 1;

                [$value, $params] = parseStringToken('""', $segments);

                if (containsChinese($value) && $hasInterp) {
                    if (! isSafePosition($tokens, $startIdx, $endIdx) || isInDeclarationStatement($tokens, $startIdx)) {
                        $stats['skipped']++;
                    } else {
                        $lineNo = lineAtOffset($code, $startOffset);
                        $key = entryKeyFor($value, $prefix, $rel . ':' . $lineNo);
                        $repl[] = [$startOffset, $endOffset, buildReplacement($key, $params)];
                        $stats['extracted']++;
                    }
                }

                $i = ($endIdx < $n) ? $endIdx + 1 : $n;
                continue;
            }
            $i++;
            continue;
        }

        [$id, $text, $line] = $t;

        if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
            $i++;
            continue;
        }

        if ($id === T_START_HEREDOC || (defined('T_START_NOWDOC') && $id === T_START_NOWDOC)) {
            $stats['heredoc']++;
            $i++;
            continue;
        }

        if ($id === T_CONSTANT_ENCAPSED_STRING) {
            $startOffset = offsetAt($tokens, $i);
            $endOffset = $startOffset + strlen($text);
            [$value, $params] = parseStringToken($text);

            if (containsChinese($value)) {
                if (! isSafePosition($tokens, $i, $i) || isInDeclarationStatement($tokens, $i)) {
                    $stats['skipped']++;
                } else {
                    $key = entryKeyFor($value, $prefix, $rel . ':' . $line);
                    $repl[] = [$startOffset, $endOffset, buildReplacement($key, $params)];
                    $stats['extracted']++;
                }
            }
        }

        $i++;
    }

    if ($repl) {
        $pendingReplacements[$file] = $repl;
    }
}

/** 生成替换文本 __('messages.key', ['name' => $expr]) */
function buildReplacement(string $key, array $params): string
{
    $call = "__('messages.{$key}'";
    if ($params) {
        $args = [];
        foreach ($params as $name => $expr) {
            $args[] = "'{$name}' => {$expr}";
        }
        $call .= ', [' . implode(', ', $args) . ']';
    }
    return $call . ')';
}

/** 计算 token 偏移 */
function offsetAt(array $tokens, int $idx): int
{
    $off = 0;
    for ($k = 0; $k < $idx; $k++) {
        $off += is_string($tokens[$k]) ? strlen($tokens[$k]) : strlen($tokens[$k][1]);
    }
    return $off;
}

/** 获取 token 行号 */
function lineAt(array $tokens, int $idx): int
{
    $t = $tokens[$idx];
    return is_array($t) ? $t[2] : 0;
}

/** 根据字节偏移计算行号 */
function lineAtOffset(string $code, int $offset): int
{
    return substr_count($code, "\n", 0, $offset) + 1;
}

/* ---------- 生成 lang 文件 ---------- */

function dumpValue($value): string
{
    return var_export($value, true);
}

function writeLangFile(string $path, array $entries, array $sources, string $lang, bool $placeholder): void
{
    $existing = is_file($path) ? include $path : [];
    if (! is_array($existing)) $existing = [];

    $lines = [];
    $lines[] = '<?php';
    if ($placeholder) {
        $lines[] = '';
        $lines[] = '// TODO: 以下条目为中文原文占位，请人工翻译为 ' . strtoupper($lang) . '。';
    }
    $lines[] = '';
    $lines[] = 'return [';

    foreach ($entries as $key => $value) {
        $srcLines = array_map(fn ($s) => '    // source: ' . $s, $sources[$key] ?? []);
        $lines = array_merge($lines, $srcLines);
        $lines[] = "    " . dumpValue($key) . ' => ' . dumpValue($value) . ',';
        $lines[] = '';
    }

    // 保留旧文件里未被本次扫描覆盖的条目（如人工翻译/新增）
    foreach ($existing as $key => $value) {
        if (! array_key_exists($key, $entries)) {
            $lines[] = "    " . dumpValue($key) . ' => ' . dumpValue($value) . ',';
            $lines[] = '';
        }
    }

    $lines[] = '];';
    $lines[] = '';

    file_put_contents($path, implode("\n", $lines));
}

@mkdir(dirname($zhPath), 0755, true);
@mkdir(dirname($enPath), 0755, true);

writeLangFile($zhPath, $zhEntries, $sources, 'zh', false);
writeLangFile($enPath, $enEntries, $sources, 'en', true);

/* ---------- 输出报告 ---------- */

echo "扫描目录: " . implode(', ', $options['dirs']) . PHP_EOL;
echo "扫描文件: " . count($allFiles) . PHP_EOL;
echo "提取字符串: {$stats['extracted']}，跳过（数组 key / 比较等不安全位置）: {$stats['skipped']}，跳过 heredoc: {$stats['heredoc']}" . PHP_EOL;
echo "生成语言文件:" . PHP_EOL;
echo "  - " . str_replace('\\', '/', $zhPath) . " (" . count($zhEntries) . " 条)" . PHP_EOL;
echo "  - " . str_replace('\\', '/', $enPath) . " (" . count($enEntries) . " 条)" . PHP_EOL;

if ($options['apply'] && $pendingReplacements) {
    $backupDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'i18n-backup' . DIRECTORY_SEPARATOR . date('Ymd_His');
    @mkdir($backupDir, 0755, true);

    $changed = 0;
    foreach ($pendingReplacements as $file => $repls) {
        $code = file_get_contents($file);
        // 从后往前替换，避免偏移变化
        usort($repls, fn ($a, $b) => $b[0] <=> $a[0]);
        foreach ($repls as [$start, $end, $text]) {
            $code = substr($code, 0, $start) . $text . substr($code, $end);
        }
        // 备份原文件
        $bakRel = str_replace(['\\', ':'], ['/', ''], ltrim(substr($file, strlen($root)), DIRECTORY_SEPARATOR));
        $bakPath = $backupDir . DIRECTORY_SEPARATOR . $bakRel;
        @mkdir(dirname($bakPath), 0755, true);
        copy($file, $bakPath);
        file_put_contents($file, $code);
        $changed++;
        echo "已替换: " . str_replace('\\', '/', substr($file, strlen($root) + 1)) . " ({$repls[0][2]}... 共 " . count($repls) . " 处)" . PHP_EOL;
    }
    echo "备份目录: " . str_replace('\\', '/', $backupDir) . PHP_EOL;
    echo "共修改 {$changed} 个文件。建议执行 `php artisan optimize:clear` 后再次运行本脚本（应提取 0 条）验证。";
} elseif ($options['apply']) {
    echo "应用模式：没有需要替换的中文字符串。";
} else {
    echo PHP_EOL . "预览模式完成。如确认无误，可执行：php scripts/extract_i18n.php --apply" . PHP_EOL;
}
echo PHP_EOL;
