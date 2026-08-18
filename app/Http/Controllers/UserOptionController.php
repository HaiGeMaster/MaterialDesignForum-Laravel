<?php

namespace App\Http\Controllers;

use App\Models\UserOption;
use Illuminate\Http\Request;

class UserOptionController extends Controller
{
    /**
     * 获取当前用户指定名称的选项。
     * value 在库中以 JSON 字符串存储，返回时解码为原始结构。
     */
    public function show(Request $request, string $name)
    {
        $option = UserOption::query()
            ->where('user_id', $request->user()->user_id)
            ->where('name', $name)
            ->first();

        return response()->json([
            'name' => $name,
            'value' => $option ? json_decode($option->value, true) : null,
        ]);
    }

    /**
     * 保存当前用户指定名称的选项（存在则更新，否则创建）。
     * value 以 JSON 字符串落库。
     */
    public function update(Request $request, string $name)
    {
        $validated = $request->validate([
            'value' => 'required|array',
        ]);

        UserOption::query()->updateOrCreate(
            ['user_id' => $request->user()->user_id, 'name' => $name],
            ['value' => json_encode($validated['value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
        );

        return response()->json(['is_set' => true]);
    }
}
