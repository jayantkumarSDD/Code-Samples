<?php

namespace App\Http\Controllers\Front;

Use App\Http\Controllers\Controller;
Use Illuminate\Http\Request;
Use Validator;
Use Redirect;
Use Session;
Use App\Models\Plans;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Auth;
use App\Models\BillingInfo;
use App\Models\Orders;
use App\Models\OrderItems;
Use Mail;

class CheckoutController extends Controller {
    
    use \App\Traits\CartTrait;
    
    public function showCheckoutPage(){
        if(!Auth::user() && !Session::get('anonymous_user'))
        {
            return Redirect::to('/account/login');
        }
        $cartData = $this->getCartData();
        if(!empty($cartData)):
            return View('/frontend/checkout/checkout-new', compact('cartData'))->with('page_title','OmtReview :: Checkout');
        else: 
            return Redirect::to('/');
        endif;
    }

    public function charge(Request $request){
        try {
            $input = $request->all();
            if(Session::get('anonymous_user')){
                $user  = Session::get('anonymous_user');
            } else if(Auth::user()) {
                $user  = Auth::user();
            } else {
                return Redirect::to('/account/login');
            }
            
            
            $cartData = $this->getCartData();
            if($cartData['is_book_product_only']){
                Stripe::setApiKey(\Config::get('stripe.STRIPE_BOOK_SALES_SECRET_KEY'));
            } else {
                Stripe::setApiKey(\Config::get('stripe.STRIPE_SECRET_KEY'));
            }
            
            $customer = Customer::create(array(
                'email' => $user->email,
                'source' => $request->stripeToken
            ));
           
            
            $charge = Charge::create(array(
                'customer' => $customer->id,
                'amount' => $cartData['cart_total']*100,
                'currency' => 'usd'
            ));
            
            
            if($charge->status == 'succeeded')
            {    
                $this->addPaymentSubscription($charge,$cartData,$user);
                $this->updateBillingDetails($input,$user);
                
                $this->sendOrderConfirmationEmail($user, $charge, $cartData);
                
                Session::put('transaction_details', $charge);
                return Redirect::to('/order-confirmation');
            } else {
                return Redirect::back()->with('error_message', 'Something went wrong');
            }
        } catch (\Exception $ex) {
            return Redirect::back()->with('error_message', $ex->getMessage());
        }
    }
    
    public function addPaymentSubscription($charge,$cartData,$user){
       
        $order = Orders::create([
                'user_id' => $user->id,
                'payment_id' => $charge->id,
                'sub_total' => $cartData['sub_total'],
                'total' => $cartData['formatted_cart_total'],
                'discount_code' => $cartData['discount_code'],
                'discount_amount' => $cartData['discount_amount'],
                'payment_data' => serialize($charge),
                'payment_mode' => \Config::get('stripe.STRIPE_MODE')
        ]);
        
        
       
        foreach($cartData['cart_items'] as $cart_items):
            OrderItems::create([
                'order_id' => $order->id,
                'plan_id'  => $cart_items->id
            ]);
        endforeach;
    }

    public function updateBillingDetails($vars,$user){
        $vars['firstname'] = $user->first_name;
        $vars['lastname'] = $user->first_name;
        $vars['user_id'] = $user->id;
        BillingInfo::addOrUpdateBillingInfo($vars);
    }
    
    public function sendOrderConfirmationEmail($user, $transaction_details, $cartData) {
        
        if($cartData['has_book_on_cart']){
            $this->sendBookShippingEmail($user);
        }
        
        $data['user'] = $user;
        $data['transaction_details'] = $transaction_details;
        $data['cart_data'] = $cartData;
        try {
            $status = Mail::send('emails.order-confirmation', $data, function($message) use ($user, $transaction_details) {
                        $message->to($user->email, $user->full_name)->subject('Order Confirmation - Your Order with Omtreview.com [' . $transaction_details->id . '] has been successfully placed!');
                    });
        } catch (Exception $e) {

        }
        return true;
    }
    
    public function sendBookShippingEmail($user){
        $data['user'] = $user;
        try {
            $status = Mail::send('emails.shipping-company', $data, function($message) {
                        $message->to('omtreview@hotmail.com', 'Marry')->subject('OMT Review book shipping');
                      });
            
            $status = Mail::send('emails.shipping-confirmation', $data, function($message) use($user) {
                        $message->to($user->email, $user->full_name)->subject('Book shipping confirmation');
                      });
                    
        } catch (Exception $e) {

        }
    }

    public function showOrderConfirmationPage(){
        $cartData = $this->getCartData(); 
        if(!empty($cartData))
        {   
            $this->removeAllItemFromCart();
            $this->removeCouponCodeFromCart();
            Session::forget('anonymous_user'); 
            $transactionDetails = Session::get('transaction_details');
            Session::forget('transaction_details');
            return View('/frontend/checkout/order-confirmation', compact('cartData', 'transactionDetails'))->with('page_title','OmtReview :: Order Confirmation');
        } else {
            return Redirect::to('/');
        }    
    }
   
}
