<?php

namespace App\Http\Controllers\Front;

Use App\Http\Controllers\Controller;
Use Illuminate\Http\Request;
Use Validator;
Use Redirect;
Use Session;
use App\Models\Plans;
use App\Models\CouponCode;
use App\Models\UsedOMMDiscountCode;
use DB;

class CartController extends Controller {
    
    use \App\Traits\CartTrait;
    public function addToCart(Request $request){
        $input = $request->all();
        if(empty($input['productId'])){
            return Redirect::back();
        }
        $productId = $input['productId'];
        $this->addProductInCart($productId);
        return Redirect::to('/cart');
    }
    
    
    public function showCartPage(){
        $cartData = [];
        $cartData = $this->getCartData();
        if(!empty($cartData)):
            $addOnProducts = $this->getAddOnProductsByCartItems($cartData);
        endif;
        return View('/frontend/cart/cart', compact('cartData','addOnProducts'))->with('page_title','OmtReview :: Shopping Cart');
    }
    
    
    private function getAddOnProductsByCartItems($cartData){
        $addOnProducts = [];
        $category = array_column($cartData['cart_items']->toArray(), 'category');
        if(!in_array('level-1-bundle', $category) && !in_array('level-2-bundle', $category) && !in_array('level-3-bundle', $category) && (!in_array('video', $category) || !in_array('flashcard', $category))){
            $cartItems = $this->getUserCartDetails();
            $addOnProducts = Plans::getAddOnProducts($cartItems);
        }
        return $addOnProducts;
    }

    

    
    

    public function removeAllProducts(Request $request){
        if(!empty($request->input('planId'))){
            $planId = $request->input('planId');
            $this->removeItemFromCart($planId);
        } else {
            $this->removeAllItemFromCart();
        }
    }
    
    
    public function addProductToCart(Request $request){
        $input = $request->all();
        if(!empty($input['planId'])){
            $productId = $input['planId'];
            $this->addProductInCart($productId);
        }
    }
    
    public function applyPromoCode(Request $request){
        $input = $request->all();
        $response = [];
        if(!empty($input['couponCode'])){
            $couponCode = $input['couponCode'];
            $discount_details =  CouponCode::where('type', '!=', 'qbankCategory')
                                    ->where('code',$couponCode)
                                    ->where(DB::raw('STR_TO_DATE(start_date,"%d/%m/%Y")'),'<=',DB::raw('DATE_FORMAT(NOW(),"%Y-%m-%d")'))
                                    ->where(DB::raw('STR_TO_DATE(end_date,"%d/%m/%Y")'),'>=',DB::raw('DATE_FORMAT(NOW(),"%Y-%m-%d")'))
                                    ->where('status','Enabled')
                                    ->first();
            if(empty($discount_details)){
                $response['status'] = 'fail';
                $response['status_code'] = 400;
                $response['errors'] = ['Please enter a valid coupon code'];
            } else {
                $this->setCartCouponCode($couponCode);
                $cartData = $this->getCartData();
                
                
                $response['status'] = 'success';
                $response['status_code'] = 200;
                $response['result'] = $cartData;
            }
        } else {
            $response['status'] = 'fail';
            $response['status_code'] = 400;
            $response['errors'] = ['Please enter a coupon code'];
        }
        return $response; 
    }
    
    public function removePromoCode(){
        $response = [];
        
        $this->removeCouponCodeFromCart();
        
        $cartData = $this->getCartData();
       
                
        $response['status'] = 'success';
        $response['status_code'] = 200;
        $response['result'] = $cartData;
        
        return $response;
    }

    public function applyUnlockOMMQbankCouponCode(Request $request){
        $input = $request->all();
        $response = [];
        if(!empty($input['coupon_code'])){
            $couponCode = $input['coupon_code'];
            $discount_details = CouponCode::whereNotIn('id', function($query){
                                            $query->select('coupon_code_id')
                                            ->from(with(new UsedOMMDiscountCode)->getTable());
                                        })
                                    ->where('type', '=', 'qbankCategory')
                                    ->where('code',$couponCode)
                                    ->where('status','Enabled')
                                    ->first();
            if(empty($discount_details)){
                $response['status'] = 'fail';
                $response['status_code'] = 400;
                $response['errors'] = ['Please enter a valid coupon code'];
            } else {
                
                UsedOMMDiscountCode::create([
                    'user_id' => \Auth::user()->id,
                    'coupon_code_id' => $discount_details->id
                ]);
                
                $response['status'] = 'success';
                $response['status_code'] = 200;
                $response['message'] = 'OMM Qbank question has been unlocked';
                $response['result'] = [];
            }
        } else {
            $response['status'] = 'fail';
            $response['status_code'] = 400;
            $response['errors'] = ['Please enter a coupon code'];
        }
        return $response; 
    }
   
}
