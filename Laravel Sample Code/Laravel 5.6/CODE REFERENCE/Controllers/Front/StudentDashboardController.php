<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Redirect;


class StudentDashboardController extends Controller
{
   
    public function showDashboard() {
        $exam_type = getExamType();
        if($exam_type == 'comlex_level_1'){
            $redirectTo = '/student/comlex-level-1-dashboard';
        } else if($exam_type == 'comlex_level_2') {
            $redirectTo = '/student/comlex-level-2-dashboard';
        } else {
            $redirectTo = '/student/comlex-level-1-dashboard';
        }
        return Redirect::to($redirectTo);
    }
}