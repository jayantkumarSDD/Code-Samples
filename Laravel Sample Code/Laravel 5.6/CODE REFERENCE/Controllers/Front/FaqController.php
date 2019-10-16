<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Faq;

class FaqController extends Controller
{
    public function showPage()
    {
        $faqs = Faq::where('status','enabled')->get();
        return view('frontend.company.faq',compact('faqs'));
    }
}
