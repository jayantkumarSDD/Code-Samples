<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FlashCard;
use DB;
class StudentAssessments extends Model
{
    protected $table = 'user_flash_cards';
    protected $fillable = ['flash_cards_id','user_id','response','last_attempt'];

	public function user(){
    	return $this->belongsTo('App\Models\User');
	}

	public static function getFlashCardCalculation($userId){
		$response['yes'] = 0;$response['no'] = 0;$response['not_sure'] = 0;$response['totalAttempted'] = 0;$response['not_attempted'] = 0;$response['totalViewed'] = 0;$response['percentCovered'] = 0;$response['totalCards'] = 0;
		$userCal = Self::select( DB::raw('count(response) as count, response') ) 
		->where('user_id', $userId)
		->groupBy('response')
		->get();
		if(count($userCal)>0){
			foreach($userCal as $key=>$val){
				if($val->response == '1'){
					$response['yes'] = $val->count;
				}else if($val->response == '2'){
					$response['no'] = $val->count;
				}else if($val->response == '3'){
					$response['not_sure'] = $val->count;
				}else if($val->response == '4'){
					$response['not_attempted'] = $val->count;
				}
			}
		}
		$l_at_cnt = Self::where('user_id',$userId)->where('last_attempt','1')->select('response')->first();
		$response['totalCards'] = Flashcard::where('status','Enabled')->count();
		$totalViewedInfo = Self::where('user_id',$userId)->where('last_attempt','1')->select('id')->first();
		if(isset($totalViewedInfo) && !empty($totalViewedInfo)){
			$response['totalViewed'] = Self::where('id','<=',$totalViewedInfo['id'])->where('user_id',$userId)->count();
		} 
		if($response['totalCards']>0){
			$response['percentCovered'] = number_format((float)($response['totalViewed'] / $response['totalCards']) *100, 2, '.', '');
		}
		$response['totalAttempted'] = $response['yes'] + $response['no'] + $response['not_sure'];
			$response['not_attempted'] = $response['not_attempted'] - 1;
			return $response;
	}

	public static function getFlashCardCalculationFilter($result, $userId, $filterBy,$nextCard,$direction){
		
		$filterArray = ['1'=>'yes','2'=>'no','3'=>'not_sure','4'=>'not_attempted'];
		$filtertel = $filterArray[$filterBy];
		$filterCount = $result[$filtertel];
		if($filterCount != 0){
			$result['totalCards'] = $filterCount;
			if(!empty($nextCard['currentFlashId'])){
				$totalViewedFilterValue = Self::where('flash_cards_id','<=',$nextCard['currentFlashId'])->where('user_id',$userId)->where('response',$filterBy)->count();
					$result['totalViewed'] = $totalViewedFilterValue;
			}else{
				$result['totalViewed'] = 0;
			}	
			if($result['totalCards']>0){
				$result['percentCovered'] = number_format((float)($result['totalViewed'] / $result['totalCards']) *100, 2, '.', '');
			}
		}else{
			$result['percentCovered'] = 0;
			$result['totalViewed'] = 0;
			$result['totalCards'] = 0;
		}
		return $result;
	}

	public static function getCurrentPageInfo($userId,$filterBy = ''){
		$query = Self::query();
		$query = $query->where('user_id',$userId)->where('last_attempt','1');
		if(isset($filterBy) && !empty($filterBy)){ 
			$query = $query->where('response', $filterBy);
		}
		if($filterBy == '4'){
			$cntThis = Self::where('user_id',$userId)->where('response','4')->count();
			if($cntThis == 1){
				return [];
			}
		} 
		return $query->get();
	}

	public static function getNextPageInfo($userId,$flash_card_id,$filterBy = ''){
		$query = Self::query();
		$query = $query->where('user_id',$userId)->where('flash_cards_id','>',$flash_card_id)->limit(1)->orderBy('id','ASC');
		if(isset($filterBy) && !empty($filterBy)){
			$query = $query->where('response', $filterBy);
		}
		return $query->get();
	}

	public static function getPrevPageInfo($userId,$flash_card_id,$filterBy = ''){
		$query = Self::query();
		$query = $query->where('user_id',$userId)->where('flash_cards_id','<',$flash_card_id)->limit(1)->orderBy('id','DESC');
		if(isset($filterBy) && !empty($filterBy)){
			$query = $query->where('response', $filterBy);
		}
		return $query->get();
	}
	
	public static function updateCardState($userId,$current_card_id,$pre_card_id){
		self::where('user_id',$userId)->where('flash_cards_id',$current_card_id)->update(['last_attempt'=>'0']);
		self::where('user_id',$userId)->where('flash_cards_id',$pre_card_id)->update(['last_attempt'=>'1']);
		return;
	}

	public static function updateCardLastAttempt($userId,$flash_cards_id,$status,$last_attempt){
		if(isset($status) && !empty($status)){
		self::where('user_id',$userId)->where('flash_cards_id',$flash_cards_id)->update(['response'=>$status,'last_attempt'=>$last_attempt]);
		}else{
			self::where('user_id',$userId)->where('flash_cards_id',$flash_cards_id)->update(['last_attempt'=>$last_attempt]);
		}
	}

	public static function updateById($id,$last_attempt){
		self::where('id',$id)->update(['last_attempt'=>$last_attempt]);
	}

	public static function resetUserFlashcards($userId){
		return self::where('user_id',$userId)->delete();	
	}



	public static function checkLastWithResponse($flash_cards_id,$userId){
		$lastInfo = Self::where('user_id',$userId)->where('flash_cards_id','>',$flash_cards_id)->where('response','4')->orderBy('id','ASC')->count();
		if($lastInfo >= 1){
				return true;
			}return false;
		}

 public static function updateStatusByAll($current_card_id,$next_card_id,$pre_card_id,$status){
		
		if(empty($next_card_id) && !empty($current_card_id) && !empty($pre_card_id)){ 
			$changedResponse = Self::where('flash_cards_id',$current_card_id)->select('response')->first();
			if($changedResponse['response'] != $status){
				
				self::where('flash_cards_id',$current_card_id)->update(['last_attempt'=>'0']);
				self::where('flash_cards_id',$pre_card_id)->update(['last_attempt'=>'1']);		
			}
		}return true;
	
 } 		
	
}
