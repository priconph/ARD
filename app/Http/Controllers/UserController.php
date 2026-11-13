<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;

class UserController extends Controller
{
    //
    public function index() {
        session_start();
        return view('index')->with('user_level_id', $_SESSION['rapidx_user_level_id']);
    }
    
    public function user_logout(){
        session_start();
        session_unset(); 
        session_destroy();
        Auth::logout();
        return response()->json(['result' => "1"]);
    }

    public function get_users_by_stat(Request $request) {
        session_start();
    	$users;
    	if($request->user_stat == 0) {
    		$users = User::on('rapidx')->get();
    	}
    	else{
    		$users = User::on('rapidx')->where('user_stat', $request->user_stat)->get();
    	}

    	return response()->json(['users' => $users]);
    }
}
