<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Session;

class CouponCode extends Model
{
    
    protected $table = 'coupon_code';
    protected $fillable = ['title', 'code', 'type', 'from_amount', 'to_amount', 'off_amount', 'percentage', 'start_date', 'end_date', 'status'];
    
    public static function addUpdateCouponCode($vars) {
        $discount = CouponCode::firstOrNew(['id' => isset($vars['id']) ? $vars['id'] : '']);
        $discount->title = $vars['title'];
        $discount->code = $vars['code'];
        $discount->type = $vars['type'];
        if($vars['type'] == 'percentage')
        {
            $discount->percentage = $vars['percentage'];
        }
        else if($vars['type'] == 'fixed')
        {
            $discount->from_amount = $vars['from_amount'];
            $discount->to_amount = $vars['to_amount'];
            $discount->off_amount = $vars['off_amount'];
        }
        else if($vars['type'] == 'qbankCategory')
        {
            $discount->qbankCategories = implode(',' , $vars['qbankCategories']);          
        }
        
        $discount->start_date = $vars['start_date'];
        $discount->end_date = $vars['end_date'];
        $discount->status = $vars['status'];
        $status = $discount->save();
        return $status;
    }
    
    
}
