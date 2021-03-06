<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Validator;
use Hash;

class UserController extends Controller
{

    public $successStatus = 200;

    public function getUser()
    {
        $data = User::get();
        return response()->json($data, $this->successStatus);
    }

    public function searchUser($id)
    {
        $data = User::find($id);
        return response()->json($data, $this->successStatus);
    }

    public function postUser(Request $req)
    {
        $validate = Validator::make($req->all(),
        [
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required'
        ]);

        if($validate->fails())
        {
            return response()->json($validate->messages(), $this->successStatus);
        }

        $user = new User();
        $user->name = $req->name;
        $user->email = $req->email;
        $user->password = bcrypt($req->password);
        $user->role = $req->role ? $req->role : "admin";
        $user->save();

        $last_data = User::find($user->id);
        return response()->json(['message' => 'User added', 'data' => $last_data],$this->successStatus);
    }

    public function deleteUser(Request $req)
    {
        $data = User::find($req->id);
        $data->delete();

        $last_data = User::all();
        return response()->json(['message' => 'User deleted', 'data' => $last_data], $this->successStatus);
    }

    public function updateUser(Request $req)
    {
        $validate = Validator::make($req->all(),
        [
            'name' => 'required',
            'email' => 'required|unique:users,'.$req->id
        ]);

        if($validate->fails())
        {
            return response()->json($validate->messages(), $this->successStatus);
        }

        $user = User::find($req->id);
        $user->name = $req->name;
        $user->email = $req->email;
        if($req->password != null || $req->password != '')
        {
            $user->password = Hash::make($req->password);
        }
        $user->save();

        return response()->json(['message' => 'User updated'],$this->successStatus);
    }
}
