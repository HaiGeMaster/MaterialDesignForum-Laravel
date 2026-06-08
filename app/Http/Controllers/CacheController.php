<?php

namespace App\Http\Controllers;

use App\Models\Cache as CacheModel;
use App\Services\Share;
// use Illuminate\Support\Facades\Cache;
//由于laraval自带缓存，所以这里直接使用laravel的缓存
class CacheController extends Controller
{
    /**
     * 创建验证码
     * @param string $name 验证码名称
     * @return string 验证码
     */
    public static function CreateCaptcha(string $name)
    {
        $code = '';
        $randcodestr = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ1234567890';
        for ($i = 0; $i < 5; $i++) {
            $code .= $randcodestr[rand(0, strlen($randcodestr) - 1)];
        }

        $md5code = md5($code);

        // Cache::put($name, $md5code, 60 * 5);

        //先检查数据库是否存在，以及检查是否过期
        $captcha = CacheModel::where('name', '=', $name)->first();
        if ($captcha) {
            //如果过期了
            // if ($captcha->life_time < Share::ServerTime()) {
                $captcha->value = $md5code;
                $captcha->life_time = Share::ServerTime() + 60 * 5;
                $captcha->save();
            // }
        }else{
            CacheModel::create([
                'name' => $name,
                'value' => $md5code,
                'create_time' => Share::ServerTime(),
                'life_time' => Share::ServerTime() + 60 * 5,
            ]);
        }
        return $code;
    }
    /**
     * 是否有效的验证码
     * @param string $name 验证码名称
     * @return bool
     */
    public static function IsVaildCaptcha(string $name, string $code_md5 = ''): bool
    {
        $query = CacheModel::where('name', '=', $name)->where('life_time', '>', Share::ServerTime());
        if ($code_md5 !== '') {
            $query->where('value', '=', $code_md5);
        }
        $captcha = $query->first();
        if ($captcha) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 是否有效的图片验证码
     * @param string $value 验证码名称
     * @return bool
     */
    public static function IsVaildImgCaptcha($value):bool{
        $query = CacheModel::where('value', '=', $value)->where('life_time', '>', Share::ServerTime())->first();
        if ($query) {
            return true;
        } else {
            return false;
        }
        
    }
    /**
     * 删除验证码
     * @param string $name 验证码名称
     * @return void
     */
    public static function DeleteCaptcha(string $name)
    {
        CacheModel::where('name', '=', $name)->delete();
        CacheModel::where('life_time', '<', Share::ServerTime())->delete();
    }
}
