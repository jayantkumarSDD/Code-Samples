<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class PaymentSubscriptions extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'payment_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['payment_id', 'plan', 'user_id', 'actutal_amount', 'discount_code', 'discount_amonut', 'amount', 'start_subscription_date', 'end_subscription_date', 'payment_data'];

    public static function checkPlanExistsInCurrentUser($plan_key = NULL) {
        $return = '';
        if (\Auth::user() && !empty($plan_key)) {
            $user_id = \Auth::user()->id;
            $user_plan_data = PaymentSubscriptions::where('user_id', $user_id)
                    ->where(DB::raw('DATE_FORMAT(NOW(),"%Y-%m-%d %H:%i:%s")'), '<=', DB::raw('STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")'))
                    ->where('plan_key', '=', $plan_key)
                    ->count();
            if ($user_plan_data == 0) {
                $return = false;
            } else {
                $return = true;
            }
        } else {
            $return = false;
        }
        return $return;
    }

    public static function getActiveSubscriptionsOfUser($user_id = NULL) {
        $data = PaymentSubscriptions::where('user_id', $user_id)
                        ->where( DB::raw('STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")'),'>=',DB::raw('STR_TO_DATE("'.date("d/m/Y H:i:s").'","%d/%m/%Y %H:%i:%s")'))
                        ->groupBy('plan')
                        ->select(DB::raw('payment_subscriptions.*,min(STR_TO_DATE(start_subscription_date,"%d/%m/%Y %H:%i:%s")) as start_subscription_date,max(STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")) as end_subscription_date'))
                        ->get()->toArray();
        return $data;
    }
    
    
    public static function getActiveSubscriptionsOfUserByPlan($user_id = NULL, $plan = NULL) {
        $data = PaymentSubscriptions::where('user_id', $user_id)
                        ->where( DB::raw('STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")'),'>=',DB::raw('STR_TO_DATE("'.date("d/m/Y H:i:s").'","%d/%m/%Y %H:%i:%s")'))
                        ->where('plan',$plan)
                        ->orderBy('id','DESC')
                        ->select( DB::raw('sum(datediff(STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s"),STR_TO_DATE("'.date("d/m/Y H:i:s").'","%d/%m/%Y %H:%i:%s"))) as add_days') )
                        ->first();
        return $data;
    }
    
    public static function getSubscriptionsOfUser($user_id = NULL) {
        $data = PaymentSubscriptions::where('user_id', $user_id)
                        ->orderBy('id','DESC')
                        ->get()->toArray();
        return $data;
    }
    public static function getExistsPlanOfUser($user_id) {
        $user_plan_data = PaymentSubscriptions::where('user_id', $user_id)
                        ->where( DB::raw('STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")'),'>=',DB::raw('STR_TO_DATE("'.date("d/m/Y H:i:s").'","%d/%m/%Y %H:%i:%s")'))
                        ->select('plan_key')->get()->toArray();
        $already_exists = array_column($user_plan_data, 'plan_key');
        return $already_exists;
    }
    public static function getExpireSubscriptionsOfUser($user_id = NULL)
    {
        $data = PaymentSubscriptions::where('user_id', $user_id)
                        ->where( DB::raw('STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")'),'<=',DB::raw('STR_TO_DATE("'.date("d/m/Y H:i:s").'","%d/%m/%Y %H:%i:%s")'))
                        ->get()->toArray();
        return $data;
    }
    public static function getNclexRnActiveSubscriptionsOfUser($user_id = NULL) {
        $data = PaymentSubscriptions::where('user_id', $user_id)
                        ->where( DB::raw('STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")'),'>=',DB::raw('STR_TO_DATE("'.date("d/m/Y H:i:s").'","%d/%m/%Y %H:%i:%s")'))
                        ->whereIn('plan',array('basic','gold','platinum','lifetime'))
                        ->groupBy('plan')
                        ->select(DB::raw('payment_subscriptions.*,min(STR_TO_DATE(start_subscription_date,"%d/%m/%Y %H:%i:%s")) as start_subscription_date,max(STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")) as end_subscription_date'))
                        ->get()->toArray();
        return $data;
    }
    
    public static function getNclexPnActiveSubscriptionsOfUser($user_id = NULL) {
        $data = PaymentSubscriptions::where('user_id', $user_id)
                        ->where( DB::raw('STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")'),'>=',DB::raw('STR_TO_DATE("'.date("d/m/Y H:i:s").'","%d/%m/%Y %H:%i:%s")'))
                        ->whereIn('plan',array('pn_basic','pn_gold','pn_platinum','pn_lifetime'))
                        ->groupBy('plan')
                        ->select(DB::raw('payment_subscriptions.*,min(STR_TO_DATE(start_subscription_date,"%d/%m/%Y %H:%i:%s")) as start_subscription_date,max(STR_TO_DATE(end_subscription_date,"%d/%m/%Y %H:%i:%s")) as end_subscription_date'))
                        ->get()->toArray();
        return $data;
    }
    
    public static function getNclexRnSubscriptionsCountOfUser($user_id = NULL)
    {
        $count = PaymentSubscriptions::where('user_id', $user_id)
                                       ->whereIn('plan',array('basic','gold','platinum','lifetime'))
                                       ->count();
        return $count;
    }
    
    public static function getNclexPnSubscriptionsCountOfUser($user_id = NULL)
    {
        $count = PaymentSubscriptions::where('user_id', $user_id)
                                       ->whereIn('plan',array('pn_basic','pn_gold','pn_platinum','pn_lifetime'))
                                       ->count();
        return $count;
    }
}
