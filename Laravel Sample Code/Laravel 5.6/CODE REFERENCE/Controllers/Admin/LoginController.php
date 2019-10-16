<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
Use Validator;
Use Illuminate\Http\Request;
use Session;

class LoginController extends Controller {

    public function signIn(Request $request) {
        $this->validate($request, [
            'user_name' => 'required',
            'password' => 'required|min:6|max:30',
        ]);

        $input = $request->all();

        $remember_me = isset($input['remember_me']) ? $input['remember_me'] : false;

        if (Auth::attempt(['user_name' => $input['user_name'], 'password' => $input['password'], 'role' => 'admin', 'status' => 'enabled'], $remember_me)) {
            return redirect('admin/dashboard');
        } else {
            return redirect('admin')->withErrors('That username/password combo does not exist.');
        }
    }
    
    
    public function showLoginInPage(){
        if(Auth::user() && Auth::user()->role == 'admin'){
            return redirect('/admin/dashboard');
        } else {
            return view('/admin/login');
        }
    }

    public function logOut() {
        Auth::logout();
        return redirect('/admin');
    }

}
