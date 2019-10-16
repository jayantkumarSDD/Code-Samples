<?php

namespace App\Http\Controllers\Front;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Videos;
use Redirect;


class ComlexLevelFirstPerformanceController extends Controller
{
    use \App\Traits\ComlexLevelFirstPerformanceAnalysisTrait;
    
    public function showQbankPerformance() {
        $user_id = \Auth::user()->id;
        $performance = $this->getPerformanceByCategory($user_id);
        return View('/user/performance',  compact('performance'))->with('page_title','COMLEX Level 1 Performance Analysis');
    }
}