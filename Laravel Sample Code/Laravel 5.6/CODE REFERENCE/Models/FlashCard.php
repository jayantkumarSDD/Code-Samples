<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use DB;
 
class FlashCard extends Model
{
    protected $table = 'flash_cards';
    
protected $fillable = ['term','definition','flash_card_category_id','status'];

public function user(){
        return $this->belongsTo('App\Models\User');
    }

/*
		Function flashCardLowHighId
    	This function return the max and min record of All Flashcards run time.
    */
    public static function flashCardLowHighId(){
    	$res = [];
    	$min = 0; $max=0;
    	$lowest = Self::select('id')->orderBy('id','ASC')->limit(1)->first();
    	if(isset($lowest) && !empty($lowest)){
    		$min = $lowest['id'];
    	}
    	$highest = Self::select('id')->orderBy('id','DESC')->limit(1)->first();
    	if(isset($highest) && !empty($highest)){
    		$max = $highest['id'];
    	}
    	$res['min'] = $min;$res['max'] = $max; return $res;
    }
	/*
		Function saveGetNext
    	This function will save the current card state along with the status with filter
    */

    public static function addUpdateFlashCard($vars) {
        $flashCard = Self::firstOrNew(['id' => isset($vars['id']) ? $vars['id'] : '']);
        $flashCard->term = $vars['term'];
        $flashCard->flash_card_category_id = $vars['category'];
        $flashCard->definition = $vars['definition'];
        $flashCard->status = $vars['status'];
        $status = $flashCard->save();
        return $status;
    }

    public static function getNextPageInfo($id){
        return Self::where('id','>',$id)->where('status','Enabled')->limit(1)->get();
    }
    public static function getPrevPageInfo($id){
        return Self::where('id','<',$id)->where('status','Enabled')->limit(1)->get();
    }

    public static function getCurrentPageInfo($id){
        return Self::where('id',$id)->select('id','term','definition')->first();
    }

    public static function checkAlreadyExistTermDefinition($flashcard){
        $isExists = [];
        $term = isset($flashcard['term']) ? $flashcard['term'] : '';
        $definition = isset($flashcard['definition']) ? $flashcard['definition'] : '';
        if(!empty($term) && !empty($definition)){
            $isExists = Self::where('term',$term)->where('definition',$definition)->first();
        } return $isExists;
    }
}
