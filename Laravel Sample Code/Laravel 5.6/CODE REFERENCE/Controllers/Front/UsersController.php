<?php

namespace App\Http\Controllers\Front;

use App\Models\User;
use App\Http\Controllers\Controller;
Use Hash;
Use Session;
use Illuminate\Support\Facades\Auth;
Use Validator;
Use Illuminate\Http\Request;
Use Crypt;
Use Mail;
Use Redirect;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\BillingInfo;

class UsersController extends Controller {

    public function doLogin(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                'email' => ['required','email', 'max:100'],
                'password' => ['required', 'min:6', 'max:30'],
        ]);
        $response = [];
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $response['status'] = 'fail';
            $response['status_code'] = '400';
            $response['errors'] = $errors;
        } else {
            $user = User::checkUserLogin($input);
            if (empty($user)) {
                $response['status'] = 'fail';
                $response['status_code'] = '401';
                $response['errors'] = ['Invalid Login Credentials'];
            } else {
                if (Hash::check($input['password'], $user->password)) {
                    if ($user->status == 'enabled') {
                        $access_token = get_random_string(64);
                        $user->access_token = $access_token;
                        $user->save();
                        $remember_me = $request->has('remember_me') ? true : false; 
                        Auth::login($user,$remember_me);
                        Session::put('access_token',$access_token);
                        $response['status'] = 'success';
                        $response['status_code'] = '200';
                        $response['message'] = 'User has been loggedin successfully!';
                        $response['redirect_url'] = getRefererUrl();
                    } else {
                        $response['status'] = 'fail';
                        $response['status_code'] = '401';
                        $response['errors'] = ['Please verify your email address!'];
                    }
                } else {
                        $response['status'] = 'fail';
                        $response['status_code'] = '401';
                        $response['errors'] = ['Please enter the valid password'];
                }
            }
            
        }
        return $response;
    }
    
    public function validateUserEmail(Request $request){
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
                'email' => ['required', 'email', 'max:100']
        ]);
        $response = [];
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $response['status'] = 'fail';
            $response['status_code'] = '400';
            $response['errors'] = $errors;
        } else {
            $user = User::where('email', '=', $input['email'])->first();
            if (empty($user)) {
                $response['status'] = 'fail';
                $response['status_code'] = '401';
                $response['errors'] = ["Email address doesn't exists in our record"];
            } else {
                $response['status'] = 'success';
                $response['status_code'] = '200';
                $response['message'] = ["This email address already exists in our system. Please enter your password below."];
            }
        }
        return $response;
    }

    

    public function doSignup(Request $request){
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
                    'first_name' => ['required', 'max:30'],
                    'last_name' => ['required', 'max:30'],
                    //'user_name' => ['required', 'unique:users,user_name', 'min:6', 'max:30'],
                    'email' => ['required', 'email', 'unique:users,email', 'max:100'],
                    'password' => ['required', 'min:6', 'max:30'],
        ]);
        $response = [];
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $response['status'] = 'fail';
            $response['status_code'] = '400';
            $response['errors'] = $errors;
        } else {
            $user = User::registerUser($input);
            if ($user == false) {
                $response['status'] = 'fail';
                $response['status_code'] = '400';
                $response['errors'] = ['Something went wrong'];
            } else {
                $status = $this->send_verificatioin_link($user);
                if ($status == true) {
                    $response['status'] = 'success';
                    if(!empty($input['isCheckoutProcees']))
                    {
                        Session::put('anonymous_user',$user);
                        $response['status_code'] =   '201';
                    }
                    else {
                        $response['status_code'] =   '200';
                    }
                    $response['message'] = 'Your account has been registered successfully. Please verify your email address to countinue use our portal.';
                } else {
                    $response['status'] = 'fail';
                    $response['status_code'] = '400';
                    $response['errors'] = ['Something went wrong'];
                }
            }
        }
        return $response;
    }
    
    public function send_verificatioin_link($user) {
        if (!empty($user)) {
            $verification_code = Crypt::encrypt($user->id);
            $name = $user->first_name;
            $link = URL("/verifyaccount?code=".urlencode($verification_code));
            $data['user_name'] = $name;
            $data['link'] = $link;
            try {
                $status = Mail::queue('emails.verify-account', $data, function($message) use ($user) {
                            $message->to($user->email, $user->name)->subject('Verify Your OMTREVIEW Account');
                        });
            } catch (Exception $e) {
                
            }
        }
        return true;
    }
    
    public function verify_account(Request $request) {
        $input = $request->all();
        $input = array_map('trim', $input);
        if (!empty($input) && !empty($input['code'])):
            try {
                $user_id = Crypt::decrypt(urldecode($input['code']));
            } catch(DecryptException $e) {
                return Redirect::to('/account/login')->with('error_message', 'Something went wrong');
            }
            
            
            $user = User::find($user_id);
            if(!empty($user)){
                $user->status = 'enabled';
                $user->save();
                return Redirect::to('/account/login')->with('message', 'Email has been verified successfully.');
            }
            else {
                return Redirect::to('/');
            }
            
        endif;
    }
    
    public function doRequestForgotPassword(Request $request) {
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
            'email' => ['required','email','max:100']
        ]);
        $response = [];
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $response['status'] = 'fail';
            $response['status_code'] = '400';
            $response['errors'] = $errors;
        } else {
            $user = User::where('email', '=', $input['email'])->first();
            if (empty($user)) {
                $response['status'] = 'fail';
                $response['status_code'] = '401';
                $response['errors'] = ["Email address doesn't exists in our record"];
            } else {
                $status = $this->sendForgotPasswordLinkToUser($user);
                if($status == true)
                {
                    $response['status'] = 'success';
                    $response['status_code'] = '200';
                    $response['message'] = 'Reset password link has been sent to your email';
                }
                else
                {
                    $response['status'] = 'fail';
                    $response['status_code'] = '400';
                    $response['errors'] = ['Something went wrong'];
                }
            }
        }
        return $response;
    }
    
    public function sendForgotPasswordLinkToUser($user = NULL){
        if (!empty($user)) {
            $verification_code = Crypt::encrypt($user->id);
            $token = getCrypt(strtotime(addMinsInCurrentDate('30')));
            $name = $user->first_name;
           
            $user_data = User::find($user->id);
            $user_data->access_token = $token;
            $user_data->save();
            
            $link = URL("/do_forgot_password/". urlencode($verification_code). "/". urlencode($token));
            $data['user_name'] = $name;
            $data['link'] = $link;
            try {
                $status = Mail::send('emails.reset-password', $data, function($message) use ($user) {
                            $message->to($user->email, $user->name)->subject('Reset password Request Link');
                        });
            } catch (Exception $e) {
                
            }
        }
        return true;
    }
    
    public function doForgotPassword($user_id = NULL,$token = NULL){
        if(Auth::user()){
            Auth::logout();
        } 
        $userid = Crypt::decrypt($user_id);
        $user = User::find($userid);
        try {
            $db_token = getDecrypt($user->access_token);
            $time_token = getDecrypt($token);
        }
        catch(DecryptException $ex) {
           return Redirect::to('/account/login')->with('error_message',"Invalid link, Please try again");
        }   
        $matchtime =  strtotime(date("Y-m-d h:i:s"));
        if (empty($userid) || empty($user)){
            return Redirect::to('/account/login')->with('error_message',"Invalid link, Please try again");
        }
        else if($db_token != $time_token)
        {
            return Redirect::to('/account/login')->with('error_message',"Link has been expired, Please try again");
        }
        else if($time_token < $matchtime)
        {
             return Redirect::to('/account/login')->with('error_message',"Link has been expired, Please try again");
        }
        else
        {
            return view('frontend.forgot_password')->with('page_title', 'Forgot Password')->with('user_id', $user_id)->with('token', $token)->with('body_class', 'forgot_password');
        }
    }
    
    public function updatePassword(Request $request){
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
                    'new_password' => ['required', 'min:6', 'max:30','confirmed'],
                    'new_password_confirmation' => ['required', 'min:6','max:30'],
                    'user_id' => ['required'],
                    'token' => ['required'],
        ]);
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        } 
        else {
            $user_id = Crypt::decrypt($input['user_id']);
            $user = User::find($user_id);
            if (!empty($user)){
                $db_token = getDecrypt($user->access_token);
                $token = getDecrypt($input['token']);
                $matchtime =  strtotime(date("Y-m-d h:i:s"));
                if($db_token != $token)
                {
                    return Redirect::to('/account/login')->with('error_message',"Token has been expired, Please try again");
                }
                else if($token < $matchtime)
                {
                     return Redirect::to('/account/login')->with('error_message',"Link has been expired, Please try again");
                }
                else
                {
                    $user = User::find($user_id);
                    $user->password = bcrypt($input['new_password']);
                    $user->access_token = getCrypt(str_random(24));
                    $user->save();
                    return Redirect::to('/account/login')->with('message', 'Password has been changed successfully');
                }
            }
            else
            {
                return Redirect::back()->with('error_message',"User doesn't exists,Please try again");
            }
        }
    }
    
    public function logout()
    {
        Auth::logout();
        Session::forget('REFERER_URL');
        return Redirect::to('/');
    }
    
    public function accountSummary(){
        return View('/user/account')->with('page_title','My Account');
    }
    
    public function labValues(){
        return View('/qbank/comlex_level_first/window_lab_values');
    }
    
    public function showRegisterPage()
    {
        if(Auth::user() && Auth::user()->role == 'user') {
            return Redirect::to('/student/dashboard');
        } else {
            return view('frontend.auth.login');
        }    
    }
    
    public function editProfile(){
        $user = Auth::user();
        return View('/user/editprofile',compact('user'))->with('page_title','Account Information');
    }
    
    public function editAddress(){
        $user = Auth::user();
        return View('/user/billinginfo',compact('user'))->with('page_title','Billing Information');
    }
    
    public function updateProfile(Request $request) {
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
                    'firstname' =>  ['required','max:60'],
                    'lastname' => ['required','max:60'],
                    'address' =>  ['required','max:100'],
                    'address_2' =>  ['max:100'],
                    'country' => ['required','max:100'],
                    'zipcode' => ['required','min:4','max:15'],
                    'phone' => ['required','min:5','max:15']
        ]);
        if (!$validator->fails()) {
            $user_id = Auth::user()->id;
            $input['id'] = $user_id;
            $status = User::updateProfile($input);
            if ($status) {
                return Redirect::back()->with('message', 'Profile has been updated successfully');
            } else {
                return Redirect::back()->with('error_message', 'Something went wrong');
            }
        } else {
            return Redirect::back()->withErrors($validator);
        }
    }
    
    public function changePassword(Request $request){
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
                    'old_password' => ['required', 'min:6','max:30'],
                    'new_password' => ['required', 'min:6','max:30', 'confirmed'],
                    'new_password_confirmation' => ['required', 'min:6','max:30']
        ]);
        if (!$validator->fails()) {
            if (Hash::check($input['old_password'], Auth::user()->password)) {
                $user = User::find(Auth::user()->id);
                $user->password = bcrypt($input['new_password']);
                $user->save();
                return Redirect::back()->with('message', 'Password has been changed successfully');
            } else {
                return Redirect::back()->with('error_message', 'Current password is incorrect ');
            }
        } else {
            return Redirect::back()->withErrors($validator);
        }
    }
    
    public function setCourseType($course_slug = null){
        Session::put('examType',$course_slug);
        return Redirect::to('/student/dashboard');
    }
    
    public function updateAddress(Request $request){
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
                    'firstname' =>  ['required','max:60'],
                    'lastname' => ['required','max:60'],
                    'address' =>  ['required','max:100'],
                    'address_2' =>  ['max:100'],
                    'country' => ['required','max:100'],
                    'zipcode' => ['required','min:4','max:15'],
                    'phone' => ['required','min:5','max:15']
        ]);
        if (!$validator->fails()) {
            $user_id = Auth::user()->id;
            $input['user_id'] = $user_id;
            $status = BillingInfo::addOrUpdateBillingInfo($input);
            if ($status) {
                return Redirect::back()->with('message', 'Address has been updated successfully');
            } else {
                return Redirect::back()->with('error_message', 'Something went wrong');
            }
        } else {
            return Redirect::back()->withErrors($validator);
        }
    }
            
}
