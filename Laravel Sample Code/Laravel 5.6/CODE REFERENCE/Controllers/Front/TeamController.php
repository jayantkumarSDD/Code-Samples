<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Team;

class TeamController extends Controller
{
    public function showPage()
    {
        $teams = Team::where('status','enabled')->get();
        return view('frontend.company.team',compact('teams'));
    }
}
