<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ComlexLevelSecondQuestionsComment extends Model {
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'comlex_level_2_question_comment';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'question_id', 'comment_type', 'comment'];
    
}