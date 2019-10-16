<?php

namespace App\Http\Controllers\Admin;
Use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
Use Redirect;
use App\Http\Requests\AddUpdateUser;
use App\Models\Orders;

class OrderController extends Controller
{
    public function showLiveOrderList(Request $request){
        if($request->has('search')){
          $keyword = $request->get('search');
          $orders = Orders::join('users','orders.user_id', '=', 'users.id')
                    ->where(function($query) use ($keyword){
                                $query->orWhere('users.first_name','LIKE',"%$keyword%")     
                                      ->orWhere('users.last_name','LIKE',"%$keyword%")     
                                      ->orWhere('users.full_name','LIKE',"%$keyword%")     
                                      ->orWhere('users.email','LIKE',"%$keyword%");
                    })   
                    ->where('orders.payment_mode','live')->orderBy('orders.id','desc')->paginate(10);
        } else {
            $orders = Orders::where('payment_mode','live')->orderBy('id','desc')->paginate(10);
        }
        
        return view('/admin/orders/orderlist', compact('orders'))->with('page_title','Live Order(s) List');
    }
    
    
    
    public function showTestOrderList(Request $request){
        if($request->has('search')){
          $keyword = $request->get('search');
          $orders = Orders::join('users','orders.user_id', '=', 'users.id')
                    ->where(function($query) use ($keyword){
                                $query->orWhere('users.first_name','LIKE',"%$keyword%")     
                                      ->orWhere('users.last_name','LIKE',"%$keyword%")     
                                      ->orWhere('users.full_name','LIKE',"%$keyword%")     
                                      ->orWhere('users.email','LIKE',"%$keyword%");
                    })   
                    ->where('orders.payment_mode','test')->orderBy('orders.id','desc')->paginate(10);
        } else {
            $orders = Orders::where('payment_mode','test')->orderBy('id','desc')->paginate(10);
        }
        
        return view('/admin/orders/orderlist', compact('orders'))->with('page_title','Test Order(s) List');
    }
}
