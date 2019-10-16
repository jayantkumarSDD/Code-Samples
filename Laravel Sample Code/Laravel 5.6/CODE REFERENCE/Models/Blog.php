<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = ['status','meta_description','meta_keywords','meta_title','image','description','title'];
}
