<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use App\Models\Option as OptionModel;

class OptionController extends Controller
{
    public static function GetAll()
    {
        return OptionModel::all()->pluck('value', 'name');
    }
    public static function Get(string $name)
    {
        return response()->json(OptionModel::where('name', '=', $name)->first());
    }
    public static function Set(string $name, string $value)
    {
        return response()->json(OptionModel::where('name', '=', $name)->update(['value' => $value]));
    }
}
