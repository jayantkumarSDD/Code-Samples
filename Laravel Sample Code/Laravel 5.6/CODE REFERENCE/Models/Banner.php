<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Banner extends Model
{
    //public $timestamps  = true;

    protected $fillable = ['title','sub_title','image', 'embed_video_url', 'status'];

    public function getBanners(){

       // $data = Banner::orderBy('id','desc')->paginate(10);
      //  return $data;
    }

}
