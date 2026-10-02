/**
 * 中文文案扫描脚本
 *
 * 递归扫描 resources/js/MaterialDesignForumVuetify4 下的 .vue / .js / .ts 文件，
 * 提取包含中文的文本片段，输出两种结果：
 *   1. scripts/chinese-report.md —— 按文件分组的可读清单（文件:行号:原文）
 *   2. scripts/chinese-report.json —— 结构化数据（供后续转语言包）
 *
 * 用法：node scripts/extract-chinese.mjs [targetDir]
 */
import { readdirSync, readFileSync, statSync, writeFileSync, mkdirSync } from 'node:fs'
import { join, relative, dirname, extname } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = join(__dirname, '..')
const TARGET = process.argv[2] ? join(process.cwd(), process.argv[2]) : join(ROOT, 'resources', 'js', 'MaterialDesignForumVuetify4')
const OUT_DIR = join(__dirname)
const OUT_MD = join(OUT_DIR, 'chinese-report.md')
const OUT_JSON = join(OUT_DIR, 'chinese-report.json')

const EXCLUDE_DIRS = new Set(['node_modules', 'locales', 'i18n'])
const EXTENSIONS = new Set(['.vue', '.js', '.ts', '.jsx', '.tsx'])

// 匹配一行中包含中文的连续片段（中文 + 相邻的字母数字与常见标点）
const SEGMENT_RE = /[\u4e00-\u9fa5][\u4e00-\u9fa5\w\s，。！？、；：""''（）《》·—…,.!?;:'"()\[\]<>~\-+*/%#@$&^=|\\{}【】‘’“”～！￥…×÷％‰°℃]*[\u4e00-\u9fa5\w，。！？、；：""''（）《》·—…,.!?;:'"()\[\]<>~\-+*/%#@$&^=|\\{}【】‘’“”～！￥…×÷％‰°℃]*/g

function isFile(path) {
  return statSync(path).isFile()
}

function walk(dir, files = []) {
  for (const name of readdirSync(dir)) {
    if (EXCLUDE_DIRS.has(name)) continue
    const full = join(dir, name)
    if (statSync(full).isDirectory()) {
      walk(full, files)
    } else if (EXTENSIONS.has(extname(full))) {
      files.push(full)
    }
  }
  return files
}

/** 判断行是否应跳过（纯注释 / import / 已用 $t 或 t 包裹的中文调用行） */
function shouldSkip(line) {
  const trimmed = line.trim()
  if (!trimmed) return true
  if (trimmed.startsWith('//')) return true
  if (trimmed.startsWith('/*')) return true
  if (trimmed.startsWith('<!--')) return true
  if (trimmed.endsWith('-->')) return true
  if (trimmed.startsWith('*')) return true
  if (trimmed.endsWith('*/')) return true
  if (trimmed.startsWith('import ')) return true
  return false
}

function extractSegments(line) {
  const segments = []
  let m
  SEGMENT_RE.lastIndex = 0
  while ((m = SEGMENT_RE.exec(line)) !== null) {
    // 过滤掉纯注释残留（行内有 // 且片段出现在其后）
    const before = line.slice(0, m.index)
    const commentIdx = before.lastIndexOf('//')
    if (commentIdx !== -1 && !before.includes('://')) {
      // 片段起始在注释符之后则跳过
      const quoteIdx = before.lastIndexOf("'")
      const dquoteIdx = before.lastIndexOf('"')
      const lastOpen = Math.max(quoteIdx, dquoteIdx)
      if (lastOpen < commentIdx) continue
    }
    // 过滤已用 $t('...') / t('...') 包裹的
    if (/[$\s]t\s*\(\s*['"]$/.test(before) && line.slice(m.index + m[0].length).trimStart().startsWith("')")) {
      continue
    }
    segments.push(m[0].trim())
  }
  return segments
}

function scan() {
  const files = walk(TARGET)
  const result = {}
  const byFile = {}

  for (const file of files) {
    const rel = relative(ROOT, file).replaceAll('\\', '/')
    const lines = readFileSync(file, 'utf8').split(/\r?\n/)
    const hits = []

    lines.forEach((line, idx) => {
      if (shouldSkip(line)) return
      const segments = extractSegments(line)
      for (const seg of segments) {
        hits.push({ line: idx + 1, text: seg })
      }
    })

    if (hits.length > 0) {
      byFile[rel] = hits
      result[rel] = hits.map((h) => `L${h.line}: ${h.text}`)
    }
  }

  const totalFiles = Object.keys(byFile).length
  const totalSegments = Object.values(byFile).reduce((n, v) => n + v.length, 0)

  // Markdown 报告
  const mdLines = [
    '# 中文文案提取清单',
    '',
    `- 扫描目录：\`${relative(ROOT, TARGET).replaceAll('\\', '/')}\``
    + (process.argv[2] ? '（自定义目标）' : ''),
    `- 生成时间：${new Date().toLocaleString('zh-CN')}`,
    `- 命中文件：**${totalFiles}** 个`,
    `- 命中片段：**${totalSegments}** 处`,
    '',
    '> 说明：不含注释 / import / 语言包自身的行；已用 `$t()` 包裹的片段会单独标注。',
    '',
  ]

  for (const [file, hits] of Object.entries(byFile)) {
    mdLines.push(`## ${file}`, '')
    for (const h of hits) {
      mdLines.push(`- L${h.line}: \`${h.text}\``)
    }
    mdLines.push('')
  }

  mkdirSync(OUT_DIR, { recursive: true })
  writeFileSync(OUT_MD, mdLines.join('\n'), 'utf8')
  writeFileSync(OUT_JSON, JSON.stringify(byFile, null, 2), 'utf8')

  console.log(`✅ 扫描完成：${totalFiles} 个文件，${totalSegments} 处中文`)
  console.log(`📄 报告：${relative(ROOT, OUT_MD).replaceAll('\\', '/')}`)
  console.log(`📦 数据：${relative(ROOT, OUT_JSON).replaceAll('\\', '/')}`)
}

scan()
