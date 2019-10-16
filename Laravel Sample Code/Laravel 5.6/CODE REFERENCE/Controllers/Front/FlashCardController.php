<?php

namespace App\Http\Controllers\Front;

Use App\Http\Controllers\Controller;
Use App\Models\FlashCard;
Use App\Models\StudentAssessments;
use Illuminate\Http\Request;
Use Validator;
Use Redirect;


class FlashCardController extends Controller {
	
	
    public function takeQuiz(){
        $flashCard = FlashCard::all()->toJson();
        return View('flashcard/quiz', compact('flashCard'))->with('page_title', 'Comlex Flashcard');
    }


    /*
		Flashcard Functionility start by here
	    Function showFlashcardPage
    	Load the flashcard page
    */
    public function showFlashcardPage()
	{ 	
		$userId = \Auth::user()->id;
    	$calculatedData = $this->calculateData($userId);
    	$nextCard = $this->nextCard($userId);
    	return view('frontend.flashcards',compact('nextCard','calculatedData'))->with('page_title', 'Flashcard');	
    }
	/*
		Function nextCard
    	Load the flashcard next page by detecting the status of previous page for logged user
    */
	private function nextCard($userId){  
		$flashMaxId  = Flashcard::flashCardLowHighId();
		$nextFlashId = 0;$prevFlashId = 0;
		$nextFlashInfo = [];
			/*===If user already in user flash table data ===========*/
			$currentPageInfo = StudentAssessments::getCurrentPageInfo($userId);
			if(count($currentPageInfo)>0)
			{
					if($flashMaxId['max'] != $currentPageInfo[0]['flash_cards_id'])
					{
						$nextFlashInfo = $this->getNextCurPrevInfo($userId,$currentPageInfo[0]['flash_cards_id']);	
					}else if($flashMaxId['max'] == $currentPageInfo[0]['flash_cards_id'])
					{
						$checkPrevFLashInfo = StudentAssessments::where('user_id',$userId)->where('flash_cards_id','<',$currentPageInfo[0]['flash_cards_id'])->limit(1)->orderBy('id','DESC')->get();
						if(count($checkPrevFLashInfo)>0){
							$prevFlashId = $checkPrevFLashInfo[0]['flash_cards_id'];
						}
						$nextFlashInfo['prevFlashId'] = $prevFlashId;
						$nextFlashInfo['currentFlashId'] = $currentPageInfo[0]['flash_cards_id'];
						$nextFlashInfo['nextFlashId'] = 0;
					}
				/*===If user already in user flash table data Logic end here ===========*/
			}else{
					/*===If user not initiated yet ===========*/
					if(isset($flashMaxId['min']))
					{
						$isUpdate = StudentAssessments::create(['flash_cards_id'=>$flashMaxId['min'],'user_id'=>$userId,'last_attempt'=>'1','response'=>4]);
						$nextFlashFromFlashCard = FlashCard::getNextPageInfo($flashMaxId['min']);
						if(count($nextFlashFromFlashCard)>0){
							$nextFlashId = $nextFlashFromFlashCard[0]['id'];	
						}
						$nextFlashInfo['prevFlashId'] = $prevFlashId;
						$nextFlashInfo['currentFlashId'] = $flashMaxId['min'];	
						$nextFlashInfo['nextFlashId'] = $nextFlashId;
					}
					/*===If user not initiated yet END here ===========*/
				}
			$nextFlashInfo['currentCard'] = Flashcard::getCurrentPageInfo($nextFlashInfo['currentFlashId']);
			return $nextFlashInfo;
	}
	/*
		Function getNextCurPrevInfo
    	This function returns the current, Next, Prev state of the users Flashcard
    */

	function getNextCurPrevInfo($userId,$flash_card_id){
		$activeState = [];
		$nextFlashId = 0;$prevFlashId = 0;
		$checkNextFLashInfo = StudentAssessments::getNextPageInfo($userId,$flash_card_id);
		if(count($checkNextFLashInfo)>0){
			$nextFlashId = $checkNextFLashInfo[0]['flash_cards_id'];
		}						
		$checkPrevFLashInfo = StudentAssessments::getPrevPageInfo($userId,$flash_card_id);
			
		if(count($checkPrevFLashInfo)>0){
			$prevFlashId = $checkPrevFLashInfo[0]['flash_cards_id'];
		}
		if($nextFlashId == 0 && $prevFlashId != 0){
			$nextFlashFromFlashCard = FlashCard::getNextPageInfo($flash_card_id);
			if(count($nextFlashFromFlashCard)>0){
				$nextFlashId = $nextFlashFromFlashCard[0]['id'];	
			}
		}else if($nextFlashId != 0 && $prevFlashId == 0){
			$prevFlashFromFlashCard = FlashCard::getPrevPageInfo($flash_card_id);
			
			if(count($prevFlashFromFlashCard)>0){
				$prevFlashId = $prevFlashFromFlashCard[0]['id'];	
			}
		}else if($nextFlashId == 0 && $prevFlashId == 0){
			$nextFlashFromFlashCard = FlashCard::getNextPageInfo($flash_card_id);
			$prevFlashFromFlashCard = FlashCard::getPrevPageInfo($flash_card_id);
			if(count($nextFlashFromFlashCard)>0){
				$nextFlashId = $nextFlashFromFlashCard[0]['id'];	
			}
			if(count($prevFlashFromFlashCard)>0){
				$prevFlashId = $prevFlashFromFlashCard[0]['id'];	
			}
		}
		$activeState['prevFlashId'] = $prevFlashId;
		$activeState['currentFlashId'] = $flash_card_id;	
		$activeState['nextFlashId'] = $nextFlashId;
		return $activeState;
	}

	/*
		Function nextCardWithFilter
    	This function filter the data and let do the pagination only with the user flash cards	
    */
	function nextCardWithFilter($userId,$filterBy){
		$flashMaxId  = Flashcard::flashCardLowHighId();
    	$nextFlashId = 0;$prevFlashId = 0;
		$nextFlashInfo = [];
		$currentPageInfo = StudentAssessments::getCurrentPageInfo($userId,$filterBy);
		 
		if(count($currentPageInfo)>0){
			if($flashMaxId['max'] != $currentPageInfo[0]['flash_cards_id'])
			{
				$checkNextFLashInfo = StudentAssessments::getNextPageInfo($userId,$currentPageInfo[0]['flash_cards_id'],$filterBy);
				if(count($checkNextFLashInfo)>0){
					$nextFlashId = $checkNextFLashInfo[0]['flash_cards_id'];
				}
				$checkPrevFLashInfo = StudentAssessments::getPrevPageInfo($userId,$currentPageInfo[0]['flash_cards_id'],$filterBy);
				if(count($checkPrevFLashInfo)>0){
						$prevFlashId = $checkPrevFLashInfo[0]['flash_cards_id'];
				}
			}
		}
		if(!empty($currentPageInfo[0]['flash_cards_id'])){
			$nextFlashInfo['currentCard'] = Flashcard::getCurrentPageInfo($currentPageInfo[0]['flash_cards_id']);
			$nextFlashInfo['currentFlashId'] = $currentPageInfo[0]['flash_cards_id']; 
		}
		if($filterBy == '4' && !empty($nextFlashId)){
			$checkLastvalue = StudentAssessments::checkLastWithResponse($nextFlashId,$userId);	
			if(!$checkLastvalue){
				$nextFlashId = 0;
			}
		}
		$nextFlashInfo['prevFlashId'] = $prevFlashId;
		$nextFlashInfo['nextFlashId'] = $nextFlashId;
		return $nextFlashInfo;
	}
	/*
		Function calculateData
    	This function calculate the user card data with response status everytime
    */
    private function calculateData($userId){
		return StudentAssessments::getFlashCardCalculation($userId);
	}
	private function calculateDataWithFilter($userId,$filterBy,$nextCard,$direction){
		$response = StudentAssessments::getFlashCardCalculation($userId);
		return StudentAssessments::getFlashCardCalculationFilter($response,$userId,$filterBy,$nextCard,$direction);
	}
	
	public function saveGetNext(Request $request)
	{ 
			$filterArray = ['yes'=>'1','no'=>'2','not_sure'=>'3','skipped'=>'4'];
    		$current_card_id = $request['current_card_id'];
    		$next_card_id = $request['next_card_id'];
    		$pre_card_id = $request['pre_card_id'];
    		$status = isset($request['status']) ? $request['status'] : '';
    		$userId = \Auth::user()->id;
			$direction = isset($request['direction']) ? $request['direction'] : '';
			$filter = isset($request['filter']) ? $request['filter'] : '';
    		$filterFlag = 0; if(!empty($filter)) { $filterFlag = 1; }
			if(isset($direction) && $direction == "pre")
			{
				$flashMaxId  = Flashcard::flashCardLowHighId();
				StudentAssessments::updateCardState($userId,$current_card_id,$pre_card_id);
			}else if(isset($direction) && $direction == "next")
			{
				$this->nextDirectionCard($userId,$current_card_id,$next_card_id,$status,$filter);
				
			}elseif(empty($status) && !empty($filter))
			{ 
				$filterFlag = $this->filterReady($userId,$filterArray[$filter]);
				
			}

			if($filterFlag){  
			StudentAssessments::updateStatusByAll($current_card_id,$next_card_id,$pre_card_id,$filterArray[$filter]);	
			$nextCard = $this->nextCardWithFilter($userId,$filterArray[$filter]);
			$nextInfo['filter'] = $filter;
			$calculatedData = $this->calculateDataWithFilter($userId,$filterArray[$filter],$nextCard,$direction);
			}else{
				$nextCard = $this->nextCard($userId);
				$calculatedData = $this->calculateData($userId);
			}
			$nextInfo['calculatedData'] = $calculatedData;
			$nextInfo['nextCard'] = $nextCard;
			return response()->json($nextInfo);
	}

	private function nextDirectionCard($userId,$current_card_id,$next_card_id,$status,$filter){
		$checkAlreadyWithStatusExist = StudentAssessments::where('user_id',$userId)->where('flash_cards_id',$current_card_id)->count();
		if($checkAlreadyWithStatusExist > 0)
		{
			$flashMaxId  = Flashcard::flashCardLowHighId();
			if($flashMaxId['max'] != $current_card_id)
			{	
				if($status != 4)
				{
					$this->updateResponse($userId,$current_card_id,$next_card_id,$status,$filter);
				}else
				{
					$this->checkAndResetResponse($userId,$current_card_id,$next_card_id,$filter);	
				}
			}elseif($flashMaxId['max'] == $current_card_id)
			{  
				if($status != 4)
				{
					StudentAssessments::updateCardLastAttempt($userId,$current_card_id,$status,$last_attempt = '1');
				}
			}
		}else{
			StudentAssessments::updateCardLastAttempt($userId,$current_card_id,'',$last_attempt = '0');
			$checkExist = StudentAssessments::where('user_id',$userId)->where('flash_cards_id',$next_card_id)->count();
			if($checkExist>0){
				StudentAssessments::updateCardLastAttempt($userId,$next_card_id,'',$last_attempt = '1');
			}else{
			StudentAssessments::create(['flash_cards_id'=>$next_card_id,'user_id'=>$userId,'last_attempt'=>'1','response'=>4]);
			}
		}
	}


	private function checkAndResetResponse($userId,$current_card_id,$next_card_id,$filter){

		StudentAssessments::updateCardLastAttempt($userId,$current_card_id,'',$last_attempt = '0');
		$checkExist = StudentAssessments::where('user_id',$userId)->where('flash_cards_id',$next_card_id)->count();
		if($checkExist>0)
		{
			StudentAssessments::updateCardLastAttempt($userId,$next_card_id,'',$last_attempt = '1');
		}else
		{
			if(!empty($filter) && $next_card_id == 0)
			{
			StudentAssessments::updateCardLastAttempt($userId,$current_card_id,'',$last_attempt = '1');
			}else
			{
			StudentAssessments::create(['flash_cards_id'=>$next_card_id,'user_id'=>$userId,'last_attempt'=>'1','response'=>4]);
			}
		}
	}

	private function updateResponse($userId,$current_card_id,$next_card_id,$status,$filter){
		StudentAssessments::updateCardLastAttempt($userId,$current_card_id,$status,$last_attempt = '0');
					$checkExist = StudentAssessments::where('user_id',$userId)->where('flash_cards_id',$next_card_id)->count();
					if($checkExist>0)
					{
						StudentAssessments::updateCardLastAttempt($userId,$next_card_id,'',$last_attempt = '1');
					}else{
							if(!empty($filter) && $next_card_id == 0){
							StudentAssessments::updateCardLastAttempt($userId,$current_card_id,$status,$last_attempt = '1');
							}else
							{
							StudentAssessments::create(['flash_cards_id'=>$next_card_id,'user_id'=>$userId,'last_attempt'=>'1','response'=>4]);
							}
						}
	}
	/*
		Function filterReady
    	This function let tell the system that user select the filter and start the pagination from particular filter by particular state.
    */
	private function filterReady($userId,$response){ 
		$lastAttempted = StudentAssessments::where('user_id',$userId)->where('last_attempt','1')->select('id')->get();
		$firstYes = StudentAssessments::where('user_id',$userId)->where('response',$response)->orderBy('id','ASC')->limit(1)->select('id')->get();
		if(count($firstYes)>0){
			if(count($lastAttempted)>0){
			StudentAssessments::updateById($lastAttempted[0]['id'],$last_attempt = '0');
			StudentAssessments::updateById($firstYes[0]['id'],$last_attempt = '1');
			}
			return true;
		} return true;

	}
	/*
		Function removefilter
    	This function will remove the current filter and let take user to initial state where user left the last cards.
    */
	public function removefilter(Request $request){
		$userId = \Auth::user()->id;
		$lastattempt = StudentAssessments::where('user_id',$userId)->where('last_attempt','1')->pluck('id')->toArray();
		$lastvalue = StudentAssessments::where('user_id',$userId)->orderBy('id','DSC')->limit(1)->first();
		StudentAssessments::updateById($lastattempt[0],$last_attempt = '0');
		StudentAssessments::updateById($lastvalue->id,$last_attempt = '1');
	}

	public function resetCard(){
		$userId = \Auth::user()->id;
		$res = StudentAssessments::resetUserFlashcards($userId);
		$this->nextCard($userId);
	}
	public function cardState(){
		$userId = \Auth::user()->id;
		$calculatedData = $this->calculateData($userId);
		return response()->json($calculatedData);
	}
    
}
