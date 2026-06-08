<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $table = 'option';
    protected $primaryKey = 'name';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
    ];

    /**
   * 获取配置项
   * @param string $name 配置项名称
   * @return string|null 配置项值
   */
  public static function Get($name)
  {
    try {
      if ($name == 'site_activation_key') {
        return null;
      }
      $option = self::find($name);
      if ($option) {
        return $option->value;
      } else {
        return null;
      }
    } catch (\Exception $e) {
      return null;
    }
  }
  /**
   * 设置配置项
   * @param string $name 配置项名称
   * @param string $value 配置项值
   * @return bool 是否设置成功
   */
  public static function Set($name, $value)
  {
    $option = self::find($name);
    if ($option) {
      $option->value = $value;
      return $option->save();
    } else {
      $option = new Option;
      $option->name = $name;
      $option->value = $value;
      return $option->save();
    }
  }
}
