<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Testimonial;
use App\Models\Plans;
use Redirect;

class HomePageController extends Controller
{
    public function showPage()
    {  
        if(\Auth::user()){
            return Redirect::to('/student/dashboard'); 
        } else {
            $banner = Banner::where('status','enabled')->first();
            $bookPlan = Plans::where('category','book')->where('status','enabled')->orderBy('id','desc')->first();
            $testimonials = Testimonial::where('status','enabled')->get();
            return view('frontend.home.home',compact('banner','testimonials','bookPlan'));
        }
        
    }
}
