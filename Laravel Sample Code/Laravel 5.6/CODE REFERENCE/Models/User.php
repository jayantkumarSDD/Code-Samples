<?php

namespace App\Models;

use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;

class User extends Authenticatable {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_name', 'email', 'password','first_name','last_name','full_name','email','role','status','access_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];
    
    protected $appends = ['is_free_trial_user','membership_access', 'hasOMMAccess'];
    
    public function flashCard()
    {
        return $this->hasMany('App\Models\StudentAssessments');
    }

    public static function registerUser($vars) {
        $user = User::create(array(
                    'user_name' => $vars['email'],
                    'first_name' => $vars['first_name'],
                    'last_name' => $vars['last_name'],
                    'full_name' => $vars['first_name'] . ' ' . $vars['last_name'],
                    'email' => $vars['email'],
                    'password' => bcrypt($vars['password'])
                ));
        if ($user) {
            return $user;
        } else {
            return false;
        }
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class);
    }
    
    public static function checkUserLogin($vars)
    {
        $response = User::where('email', '=', $vars['email'])
                    ->where('role','=','user')
                    ->first();

        return $response;
    }
    
    public static function updateAccessToken($user_id = NULL, $access_token = NULL)
    {
        if(!empty($user_id)):
            $user = User::find($user_id);
            $user->access_token = $access_token;
            $user->save();
            return $user;
        endif;    
    }
    public static function checkAdminLogin($vars = NULL)
    {
        $response = User::where('user_name', '=', $vars['user_name'])
                    ->where('status','=','enabled')
                    ->where('role','=','admin')
                    ->first();

        return $response;
    }

    public static function updateProfile($vars = NULL)
    {
        $user = User::find($vars['id']);
        $user->first_name = $vars['firstname'];
        $user->last_name = $vars['lastname'];
        $user->full_name = $vars['firstname'].' '.$vars['lastname'];
        $user->address = $vars['address'];
        $user->address2 = isset($vars['address_2'])?$vars['address_2']:'';
        $user->city = isset($vars['city'])?$vars['city']:'';
        $user->state = $vars['state'];
        $user->country = $vars['country'];
        $user->phone = $vars['phone'];
        $user->zip = $vars['zipcode'];
        return $user->save();
    }
    
    public function billingAddress()
    {
        return $this->hasOne(BillingInfo::class);
    }
    
    
    public function orders() {
        return $this->hasMany(Orders::class)->orderBy('id','desc');
    }
    
    public function usedOMMDiscountCodes(){
        return $this->hasOne(UsedOMMDiscountCode::class);
    }
    
    public function getHasOMMAccessAttribute(){
        return !empty($this->usedOMMDiscountCodes) ? true : false;
    }

    public function getIsFreeTrialUserAttribute(){
        if(isset($this->orders[0])){
            return $this->orders[0]->activeOrderItems->isEmpty() && $this->orders[0]->expireOrderItems->isEmpty() ? true : false;
        } else {
            return true;
        }
    }
    
    public function getMembershipAccessAttribute(){
        $data = [];
        if(!$this->orders->isEmpty())
        {
            foreach($this->orders as $order){
                if(!$order->activeOrderItemsDescEndSubscriptionDate->isEmpty()){
                    foreach($order->activeOrderItemsDescEndSubscriptionDate as $item){
                        switch ($item->plan->category):
                            case 'video':
                                $data['video'][] = $item;
                            break;
                            case 'flashcard':
                                $data['flashcard'][] = $item;
                            break;
                            case 'level-1-bundle':
                                $data['video'][] = $item;
                                $data['flashcard'][] = $item;
                                $data['level_1_qbank'][] = $item;
                            break;
                            case 'level-2-bundle':
                                $data['video'][] = $item;
                                $data['flashcard'][] = $item;
                                $data['level_2_qbank'][] = $item;
                            break;
                            case 'level-3-bundle':
                                $data['video'][] = $item;
                                $data['flashcard'][] = $item;
                                $data['level_3_qbank'][] = $item;
                            break;
                            case 'level-1':
                                $data['level_1_qbank'][] = $item;
                            break;
                            case 'level-2':
                                $data['level_2_qbank'][] = $item;
                            break;
                            case 'level-3':
                                $data['level_3_qbank'][] = $item;
                            break;
                        endswitch;
                    }
                }
            }
        }
        $return = $this->setAccessData($data);
      
        return $return;
    }
    
    public function setAccessData($data){
        $return = [];
        
        $video = isset($data['video']) ? $data['video'] : [];
        $return['video']['data'] = $video;
        $return['video']['hasAccess'] = !empty($video[0]->end_subscription_date) && $video[0]->end_subscription_date >= date('Y-m-d H:i:s') ? true :  false;
        
        $level_1_qbank = isset($data['level_1_qbank']) ? $data['level_1_qbank'] : [];
        $return['level_1_qbank']['data'] = $level_1_qbank;
        $return['level_1_qbank']['hasAccess'] = !empty($level_1_qbank[0]->end_subscription_date) && $level_1_qbank[0]->end_subscription_date >= date('Y-m-d H:i:s') ? true :  false;
        
        $level_2_qbank = isset($data['level_2_qbank']) ? $data['level_2_qbank'] : [];
        $return['level_2_qbank']['data'] = $level_2_qbank;
        $return['level_2_qbank']['hasAccess'] = !empty($level_2_qbank[0]->end_subscription_date) && $level_2_qbank[0]->end_subscription_date >= date('Y-m-d H:i:s') ? true :  false;
        
        $level_3_qbank = isset($data['level_3_qbank']) ? $data['level_3_qbank'] : [];
        $return['level_3_qbank']['data'] = $level_3_qbank;
        $return['level_3_qbank']['hasAccess'] = !empty($level_3_qbank[0]->end_subscription_date) && $level_3_qbank[0]->end_subscription_date >= date('Y-m-d H:i:s') ? true :  false;
        
        $flashcard = isset($data['flashcard']) ? $data['flashcard'] : [];
        $return['flashcard']['data'] = $flashcard;
        $return['flashcard']['hasAccess'] = !empty($flashcard[0]->end_subscription_date) && $flashcard[0]->end_subscription_date >= date('Y-m-d H:i:s') ? true :  false;
        
        return $return;
        
    }
    
    public static function updateUser($vars) {
        $user = Self::firstOrNew(['id'=>  isset($vars['id'])?$vars['id']:'']);;
        if(!empty($vars['email'])):
            $user->email = $vars['email'];
            $user->user_name = $vars['email'];
        endif;
        $user->first_name = $vars['first_name'];
        $user->last_name = $vars['last_name'];
        $user->full_name = $vars['first_name'] . ' ' . $vars['last_name'];
        if(!empty($vars['password'])):    
            $user->password = bcrypt($vars['password']);
        endif;
        $user->status = $vars['status'];
        $user = $user->save();
        return $user;
    }
    
}
