<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class OrderItems extends Model
{
    
    protected $table = 'order_items';
    
    protected $fillable = [
        'order_id', 'plan_id', 'validity'
    ];
    
    public function plan(){
        return $this->hasOne(Plans::class,'id', 'plan_id');
    } 
    
}
