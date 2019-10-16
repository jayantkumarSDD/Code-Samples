<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
Use App;
Use Input;
Use Validator;
Use Redirect;
Use Hash;
Use App\Models\Plans;
Use App\Models\User;
Use App\Models\PaymentSubscriptions;
Use App\Models\BillingInfo;

class MembershipController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Welcome Controller
      |--------------------------------------------------------------------------
      |
      | This controller renders the "marketing page" for the application and
      | is configured to only allow guests. Like most of the other sample
      | controllers, you are free to modify or remove it as you desire.
      |
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        
    }
    
    public function showAddMembership() {
        $plans = Plans::where('status','Enabled')->get(['id','title','price','validity']);
        return view('admin.addMembership')->with('page_title', 'Add Membership')->with('plans',$plans);
    }
    public function addMembership() {
        $input = Input::all();
        $validator = Validator::make($input, [
            'plan' => 'required',
            'first_name' => ['required','max:40'],
            'last_name' => ['required','max:40'],
            'username' => ['required','max:40','unique:users,user_name'],
            'email' => ['required','email','max:100','unique:users,email'],
            'password' => ['required','min:6','max:40','confirmed'],
            'password_confirmation' => ['required','min:6','max:40'],
            'billing_first_name' => ['required','max:40'],
            'billing_last_name' => ['required','max:40'],
            'billing_address' => ['required','max:200'],
            'billing_address2' => ['max:200'],
            'billing_country' => ['required'],
//            'billing_state' => ['required'],
//            'billing_city' => ['required'],
            'billing_zipcode' => ['required','min:3','max:15'],
            'billing_phone' => ['required','min:5','max:15']
        ]);
        if ($validator->fails()) {
            return Redirect::back()->with('error_message', 'The following errors occurred')->withErrors($validator);
        } else {
            //Do user registration
            $user = $this->doUserRegistration($input);
            if(!empty($user->id)) {
                //Do add subscription
                $input['user_id'] = $user->id; 
                $subscription = $this->addPaidMembership($input);
                if(!empty($subscription->id)){
                    return Redirect::back()->with('message', 'Membership added sucessfully');
                }
                else {
                    return Redirect::back()->with('error_message', 'Something Went wrong');
                }
            } else {
                return Redirect::back()->with('error_message', 'Something Went wrong');
            }    
        }
    }
    
    public function doUserRegistration($vars = NULL) {
        $user = User::create([
            'user_name' => $vars['username'],
            'first_name' => $vars['first_name'],
            'last_name' => $vars['last_name'],
            'full_name' => $vars['first_name'] .' '. $vars['last_name'],
            'email' => $vars['email'],
            'password' => bcrypt($vars['password']),
            'status' => 'active'
        ]);
        return $user;
    }
    
    public function addPaidMembership($vars = NULL) {
        
        $plan = Plans::find($vars['plan']);
        $end_subscription_date = date('d/m/Y H:i:s', strtotime("+$plan->validity"));
        $subscription = PaymentSubscriptions::create([
            'payment_id' => $vars['user_id'].'-CustomMembership',
            'plan' => $vars['plan'],
            'user_id' => $vars['user_id'],
            'actutal_amount' => $plan->price,
            'amount' => $plan->price,
            'start_subscription_date' => date('d/m/Y H:i:s'),
            'end_subscription_date' => $end_subscription_date
        ]);
        
        //Add Billing Information
        BillingInfo::create([
            'user_id'=>$vars['user_id'],
            'payment_subscription_id'=>$subscription->id,
            'first_name'=>$vars['billing_first_name'],
            'last_name'=>$vars['billing_last_name'],
            'address'=>$vars['billing_address'],
            'address2'=>$vars['billing_address2'],
            'city'=>isset($vars['billing_city'])?$vars['billing_city']:'',
            'state'=>isset($vars['billing_state'])?$vars['billing_state']:'',
            'country'=>$vars['billing_country'],
            'zip_code'=>$vars['billing_zipcode'],
            'phone'=>$vars['billing_phone']
        ]);
        
        return $subscription;
    }
    
}
