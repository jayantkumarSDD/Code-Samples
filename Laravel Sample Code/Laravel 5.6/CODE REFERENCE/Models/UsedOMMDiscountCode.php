<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class UsedOMMDiscountCode extends Model
{
    
    protected $fillable = ['user_id','coupon_code_id'];
    
    protected $table = 'used_omm_discount_codes';

}
