<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashCardCategory extends Model
{
    protected $table = 'flash_card_category';
    protected $fillable = ['name','status'];
    
    public static function addUpdateCategory( $vars = null ){ 
        $category = FlashCardCategory::firstOrNew(['id' => isset($vars['id']) ? $vars['id'] : '']);
        $category->name = $vars['name'];
        $category->status = $vars['status'];
        $status = $category->save();
        return $category->id;
    }

    public static function checkAlreadyExists($name){
        return self::where('name',$name)->select('id')->get();
    }
    
}
