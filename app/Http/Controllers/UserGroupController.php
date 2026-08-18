<?php

namespace App\Http\Controllers;

use App\Models\UserGroup;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    public function index()
    {
        return response()->json(UserGroup::query()->orderBy('user_group_id')->get());
    }

    public function store(Request $request)
    {
        $userGroup = UserGroup::create($request->all());
        return response()->json($userGroup, 201);
    }

    public function show(UserGroup $userGroup)
    {
        return response()->json($userGroup);
    }

    public function update(Request $request, UserGroup $userGroup)
    {
        $userGroup->fill($request->all());
        $userGroup->save();
        return response()->json($userGroup);
    }

    public function destroy(UserGroup $userGroup)
    {
        $userGroup->delete();
        return response()->json(null, 204);
    }
}
