<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ComlexLevelFirstBaseQuestion extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'comlex_level_1_base_questions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title','question','options','type'];

    public static function addUpdateQuestion( $vars = null ){
        $question = ComlexLevelFirstBaseQuestion::firstOrNew(['id' => isset($vars['id']) ? $vars['id'] : '']);
        $question->question = $vars['qbank_question'];
        $question->options = (isset($vars['qbank_option']) && !empty(array_filter($vars['qbank_option']))) ? serialize(array_filter($vars['qbank_option'])) : NULL;
        $question->type = $vars['qbank_type'];
        $status = $question->save();
        return $status;
    }
    
    public static function getQuestions($limit = NULL){
        $questions = ComlexLevelFirstBaseQuestion::orderBy('id', 'desc')->paginate($limit);
        return $questions;
    }
    
    public static function getSetsQuestions($limit = NULL){
        $questions = ComlexLevelFirstBaseQuestion::with('mainQuestions')
                            ->where('type','sets')
                            ->orderBy('id', 'desc')
                            ->paginate($limit);
        return $questions;
    }
    
    
    public static function getMatchingSetsQuestions($limit = NULL){
        $questions = ComlexLevelFirstBaseQuestion::with('mainQuestions')
                            ->where('type','matchingset')
                            ->orderBy('id', 'desc')
                            ->paginate($limit);
        return $questions;
    }
    

    public static function doSearchSetsQuestions($keyword = NULL, $limit = NULL) {
       $questions = ComlexLevelFirstBaseQuestion::with('mainQuestions')
                    ->orderBy('id', 'desc')
                    ->where(function($query) use($keyword) {
                        $query->orWhere('id', 'LIKE', "%$keyword%")
                              ->orWhere('question', 'LIKE', "%$keyword%")
                              ->orWhere('created_at', 'LIKE', "%$keyword%")
                              ->orWhere('updated_at', 'LIKE', "%$keyword%");        
                    })
                    ->where('type','sets')
                    ->paginate($limit);
       return $questions;  
    }
    
    
    public static function doSearchMatchingSetsQuestions($keyword = NULL, $limit = NULL) {
       $questions = ComlexLevelFirstBaseQuestion::with('mainQuestions')
                    ->orderBy('id', 'desc')
                    ->where(function($query) use($keyword) {
                        $query->orWhere('id', 'LIKE', "%$keyword%")
                              ->orWhere('question', 'LIKE', "%$keyword%")
                              ->orWhere('created_at', 'LIKE', "%$keyword%")
                              ->orWhere('updated_at', 'LIKE', "%$keyword%");        
                    })
                    ->where('type','matchingset')
                    ->paginate($limit);
       return $questions;  
    }
    
    public function mainQuestions()
    {
        return $this->hasMany('App\Models\ComlexLevelFirstQuestion','parent');
    }
    
}
