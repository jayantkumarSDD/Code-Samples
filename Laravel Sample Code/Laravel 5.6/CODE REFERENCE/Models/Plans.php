<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Session;

class Plans extends Model
{
    
    protected $table = 'plans';
    protected $fillable = [
                            'category',
                            'title',
                            'cart_title',
                            'description',
                            'cart_page_description',
                            'regular_price',
                            'sales_price',
                            'validity',
                            'status'
                          ];
    protected $appends = ['cart_page_label','validity_title', 'item_url'];
    
    public static function addUpdatePlan($vars) {
        $plan = Self::firstOrNew(['id' => isset($vars['id']) ? $vars['id'] : '']);
        $plan->category = $vars['category'];
        $plan->title = $vars['title'];
        $plan->cart_title = $vars['cart_title'];
        $plan->description = $vars['description'];
        $plan->cart_page_description = !empty($vars['cart_page_description']) ? $vars['cart_page_description'] : NULL;
        $plan->regular_price = $vars['regular_price'];
        $plan->sales_price = $vars['sales_price'];
        $plan->validity = $vars['validity'];
        $plan->status = $vars['status'];
        $status = $plan->save();
        return $status;
    }
    
    public function getCartPageLabelAttribute()
    {
        $cartPageLabel = '';
        switch($this->category):
            case 'level-1':
                  $cartPageLabel = 'COMLEX Level 1';
            break;
            case 'level-2':
                  $cartPageLabel = 'COMLEX Level 2';
            break;   
            case 'level-3':
                  $cartPageLabel = 'COMLEX Level 3';
            break;   
            case 'video':
                  $cartPageLabel = 'OMM Video';
            break;   
            case 'flashcard':
                  $cartPageLabel = 'Flashcard';
            break;
            case 'book':
                  $cartPageLabel = 'OMT Review Book';
            break; 
            case 'level-1-bundle':
                  $cartPageLabel = 'COMLEX Level 1 Bundle';
            break;   
            case 'level-2-bundle':
                  $cartPageLabel = 'COMLEX Level 2 Bundle';
            break;   
            case 'level-3-bundle':
                  $cartPageLabel = 'COMLEX Level 3 Bundle';
            break;   
        endswitch;
        return $cartPageLabel;
    }
    
    public function getItemUrlAttribute(){
        $itemUrl = '';
        switch($this->category):
            case 'level-1':
                  $itemUrl = '/products/level-1';
            break;
            case 'level-2':
                  $itemUrl = '/products/level-2';
            break;   
            case 'level-3':
                  $itemUrl = '/products/level-3';
            break;   
            case 'video':
                  $itemUrl = '/products/video';
            break;   
            case 'flashcard':
                  $itemUrl = '/products/flashcard';
            break;   
            case 'level-1-bundle':
                  $itemUrl = '/products/level-1';
            break;   
            case 'level-2-bundle':
                  $itemUrl = '/products/level-2';
            break;   
            case 'level-3-bundle':
                  $itemUrl = '/products/level-3';
            break;   
        endswitch;
        return $itemUrl;
    } 

    public function getValidityTitleAttribute(){
        return ($this->validity > 1) ? $this->validity.' Months' : $this->validity.' Month';
    }


    public static function getAddOnProducts($cartItems){
        $products  = Plans::whereIn('category',['video','flashcard'])
                            ->whereNotIn('id',$cartItems)   
                            ->where('status','Enabled') 
                            ->get()->toArray();
        return $products;
    }
}
