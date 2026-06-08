<?php

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
