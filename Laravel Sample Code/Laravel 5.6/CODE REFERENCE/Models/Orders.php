<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use DB;

class Orders extends Model
{
    
    protected $table = 'orders';
    
    protected $fillable = [
        'user_id', 'payment_id', 'sub_total', 'total', 'discount_code', 'discount_amount', 'payment_data','payment_mode'
    ];
    
    public function orderItems() {
        return $this->hasMany(OrderItems::class,'order_id');
    }
    
    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    } 

    public function activeOrderItems(){
        return $this->orderItems()
                    ->where('isActive','yes')
                    ->whereNotNull('end_subscription_date')
                    ->where(DB::raw('STR_TO_DATE(end_subscription_date,"%Y-%m-%d %H:%i:%s")'),'>=',DB::raw('DATE_FORMAT(NOW(),"%Y-%m-%d %H:%i:%s")'))
                    ->orderBy('id','desc');
    }
    
    public function activeOrderItemsDescEndSubscriptionDate(){
        return $this->orderItems()
                    ->where('isActive','yes')
                    ->whereNotNull('end_subscription_date')
                    ->where(DB::raw('STR_TO_DATE(end_subscription_date,"%Y-%m-%d %H:%i:%s")'),'>=',DB::raw('DATE_FORMAT(NOW(),"%Y-%m-%d %H:%i:%s")'))
                    ->orderBy('end_subscription_date','desc');
    }
    
    
    public function expireOrderItems(){
        return $this->orderItems()
                    ->where('isActive','yes')
                    ->whereNotNull('end_subscription_date')
                    ->where(DB::raw('DATE_FORMAT(NOW(),"%Y-%m-%d %H:%i:%s")'),'>',DB::raw('STR_TO_DATE(end_subscription_date,"%Y-%m-%d %H:%i:%s")'))
                    ->orderBy('id','desc');
                    
    }
    
    public function pendingOrderItems(){
        return $this->orderItems()
                    ->where('isActive','no')
                    ->whereNull('start_subscription_date')
                    ->whereNull('end_subscription_date')
                    ->orderBy('id','desc');
    }
    
}
