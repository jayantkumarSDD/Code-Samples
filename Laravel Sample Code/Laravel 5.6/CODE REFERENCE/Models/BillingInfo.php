<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingInfo extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'billing_info';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'payment_subscription_id', 'first_name', 'last_name', 'address', 'address2', 'city', 'state', 'country', 'zip_code', 'phone'];
    
    protected $appends = [
        'full_name'
    ];
    
    public static function addOrUpdateBillingInfo($vars = NULL) {
        $billing = BillingInfo::firstOrNew(['user_id' => $vars['user_id']]);
        $billing->first_name = $vars['firstname'];
        $billing->last_name = $vars['lastname'];
        $billing->address = $vars['address'];
        $billing->address2 = isset($vars['address_2']) ? $vars['address_2'] : '';
        $billing->city = isset($vars['city']) ? $vars['city'] : '';
        $billing->state = $vars['state'];
        $billing->country = $vars['country'];
        $billing->zip_code = $vars['zipcode'];
        $billing->phone = $vars['phone'];
        $billing->save();
        return $billing;
    }
    
    public static function getBillingInfoByUserId($user_id = NULL){
        return BillingInfo::find($user_id);
    }
    
    public function getFullNameAttribute(){
        return $this->first_name.' '.$this->last_name;
    }
    
}
