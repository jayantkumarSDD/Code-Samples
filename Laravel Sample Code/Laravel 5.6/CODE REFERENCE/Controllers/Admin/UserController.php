<?php

namespace App\Http\Controllers\Admin;
Use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
Use Redirect;
use App\Models\User;
use App\Http\Requests\AddUpdateUser;
use App\Models\Orders;

class UserController extends Controller
{
    public function showUserList(Request $request){
        if($request->has('search'))
        {
            $keyword = $request->input('search');
            $users = User::where('role','!=','admin')
                           ->where(function($query) use ($keyword){
                                $query->orWhere('id','LIKE',"%$keyword%")
                                      ->orWhere('user_name','LIKE',"%$keyword%")     
                                      ->orWhere('first_name','LIKE',"%$keyword%")     
                                      ->orWhere('last_name','LIKE',"%$keyword%")     
                                      ->orWhere('full_name','LIKE',"%$keyword%")     
                                      ->orWhere('email','LIKE',"%$keyword%")
                                      ->orWhere('status','LIKE',"%$keyword%")
                                      ->orWhere('created_at','LIKE',"%$keyword%")     
                                      ->orWhere('updated_at','LIKE',"%$keyword%");     
                            })           
                            ->orderBy('id','DESC')
                            ->paginate(10);
        }
        else 
        {
            $users = User::where('role','!=','admin')->orderBy('id','DESC')->paginate(10);
        }
        return view('admin.user.userlist',compact('users'))->with('page_title', ' User(s) List');
    }
    
    public function showFreeTrialUserList(Request $request){
        if($request->has('search'))
        {
            $keyword = $request->input('search');
            $users = User::whereNotIn('id', function($query){
                                            $query->select('user_id')
                                            ->from(with(new Orders)->getTable());
                                    })
                           ->where('role','!=','admin')
                           ->where(function($query) use ($keyword){
                                $query->orWhere('id','LIKE',"%$keyword%")
                                      ->orWhere('user_name','LIKE',"%$keyword%")     
                                      ->orWhere('first_name','LIKE',"%$keyword%")     
                                      ->orWhere('last_name','LIKE',"%$keyword%")     
                                      ->orWhere('full_name','LIKE',"%$keyword%")     
                                      ->orWhere('email','LIKE',"%$keyword%")
                                      ->orWhere('status','LIKE',"%$keyword%")
                                      ->orWhere('created_at','LIKE',"%$keyword%")     
                                      ->orWhere('updated_at','LIKE',"%$keyword%");     
                            })           
                            ->orderBy('id','DESC')
                            ->paginate(10);
        }
        else 
        {
            $users = User::whereNotIn('id', function($query){
                                            $query->select('user_id')
                                            ->from(with(new Orders)->getTable());
                                    })
                           ->where('role','!=','admin')
                           ->orderBy('id','DESC')
                           ->paginate(10);
        }
        return view('admin.user.free_user_list',compact('users'))->with('page_title', 'Free Trial User(s) List');
    }
    
    public function showEditUserPage($id = 0){
        if(!empty($id)):
            $user = User::find($id);
            return view('admin.user.addUpdateUser',compact('user'))->with('page_title', 'Edit User');
        else:
            return view('admin.user.addUpdateUser',compact('user'))->with('page_title', 'Add New User');
        endif;
    }
    
    public function addUpdateUser(AddUpdateUser $request){
        $vars = $request->all();
        $user = User::updateUser($vars);
        if($user){
            if($request->has('id')){
                return redirect()->back()->with('message','User has been updated successfully!');
            } else {
                return redirect()->back()->with('message','User has been created successfully!');
            }
        } else {
            return redirect()->back()->with('error_message','Something went wrong');
        }
    }
    
    
}
