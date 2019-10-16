<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Redirect;
Use Illuminate\Http\Request;
Use App\Models\Orders;
Use App\Models\OrderItems;

class SubscriptionController extends Controller
{
    public function showSubscription(){
        return View('/user/subscriptions')->with('page_title','Subscriptions'); 
    }
    
    public function showPayments(){
        return View('/user/payments')->with('page_title','Payments');
    }
    
    public function printReceipt($id){
        $order = Orders::find($id);
        return View('/user/receipt', compact('order'))->with('page_title','Print Receipt');
    }
    
    public function activateSubscription($orderId, $itemId){
        
        $orders =  Orders::where('id',$orderId)->where('user_id', \Auth::user()->id)->first();
        if(!empty($orders))
        {
            foreach($orders->pendingOrderItems as $orderItem):
                if($orderItem->id == $itemId):
                    $item = OrderItems::find($itemId);
                    if($item->isActive == 'no') {
                        $validity = $item->plan->validity;
                        $start_subscription_date = date('Y-m-d H:i:s');
                        $end_subscription_date = date("Y-m-d H:i:s", strtotime("+$validity months"));
                        $item->start_subscription_date = $start_subscription_date;
                        $item->end_subscription_date = $end_subscription_date;
                        $item->isActive = 'yes';
                        $item->save();
                    }
                endif;
            endforeach;
            return Redirect::to('/student/subscription');
        } else {
            return Redirect::back();
        } 
        

    }
    
}