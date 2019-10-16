<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\VideoCategory;
use App\Models\Videos;

class VideoController extends Controller
{
    
    public function showVideoListing() {
        $videos = VideoCategory::where('status','Enabled')->get();
        return view('user.video',compact('videos'))->with('page_title','Comlex Videos');
    }
    
    public function showVideoDetailsPage($catId = null, $videoId = null) {
        $videos = VideoCategory::where('status','Enabled')->get();
        
        $video  =   Videos::where('id',$videoId)
                      ->where('status','Enabled')
                      ->first();
        
        return view('user.video-detail', compact('videos', 'video'))->with('page_title','Comlex Videos Detail');
    }
    
}
