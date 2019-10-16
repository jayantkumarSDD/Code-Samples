<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ComlexLevelFirstExam extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'test_mode', 'question_mode','submit_type','last_resume_index','total_time_spent'];
    
    protected $table = 'comlex_level_first_exam';
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
    
    public function exam_question()
    {
        return $this->hasMany('App\Models\ComlexLevelFirstExamQuestions','exam_id');
    }
}
