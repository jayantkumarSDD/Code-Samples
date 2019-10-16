<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ComlexLevelFirstExamQuestions extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['exam_id', 'question_id', 'category_id','status','isSubmited','comment','timeSpent','is_mark','is_correct','isDisabled','selected','question_count','position'];
    
    protected $table = 'comlex_level_first_exam_questions';
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
    
    public function exam_question()
    {
        return $this->belongsTo('App\Models\ComlexLevelFirstExam','id');
    }
}
