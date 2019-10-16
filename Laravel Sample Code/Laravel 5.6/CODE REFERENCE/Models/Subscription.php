<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscription_plan';
    protected $fillable = [
                            'plan_id',
                            'plan_name',
                            'plan_desc',
                            'payment_name',
                            'payment_frequency',
                            'payment_frequency_interval',
                            'payment_cycle',
                            'amount',
                            'auto_bill_amount',
                            'initial_fail_amount_action',
                            'status'
                          ];
}
