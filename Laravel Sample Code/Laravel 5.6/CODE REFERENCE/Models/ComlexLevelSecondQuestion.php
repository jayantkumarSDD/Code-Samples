<?php

namespace App\Models;
use DB;
use Illuminate\Database\Eloquent\Model;


class ComlexLevelSecondQuestion extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'comlex_level_2_questions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['category_id','d1_category_id','d2_category_id','parent','facets','question','exhibit','options','correct_answer','explanation','status','question_count'];

    public static function addUpdateSingleAnswerQuestions($vars) {
        $question = ComlexLevelSecondQuestion::firstOrNew(['id' => isset($vars['id']) ? $vars['id'] : '']);
        $question->category_id = $vars['qbank_category'];
        $question->d1_category_id = $vars['d1_qbank_category'];
        $question->d2_category_id = $vars['d2_qbank_category'];
        $question->parent = !empty($vars['parent'])?$vars['parent']:0;
        $question->question = $vars['qbank_question'];
        $question->facets = !empty($vars['facets']) ? $vars['facets'] : NULL;
        $question->options = !empty($vars['qbank_option']) ? serialize($vars['qbank_option']) : NULL;
        $question->exhibit = !empty($vars['exhibit']) ? serialize($vars['exhibit']) : NULL;
        $question->correct_answer = serialize($vars['qbank_correct_answer']);
        $question->explanation = $vars['qbank_explation'];
        $question->status = $vars['qbank_status'];
        $status = $question->save();
        
        if (!empty($vars['parent'])){
            $child_question_count = ComlexLevelSecondQuestion::where('parent',$vars['parent'])->count();
            ComlexLevelSecondQuestion::where('parent',$vars['parent'])->update(['question_count' => $child_question_count]);
        }
        
        return $status;
    }
    
    public static function getAllWithAllAssociated($limit = NULL) {
        $records = DB::table('comlex_level_2_questions')
                ->select(\DB::raw('comlex_level_2_questions.*,(select title from comlex_level_2_category where comlex_level_2_questions.category_id = comlex_level_2_category.id ) as category,(select type from comlex_level_2_base_questions where comlex_level_2_questions.parent = comlex_level_2_base_questions.id ) as type'))
                ->orderBy('id', 'desc')
                ->paginate($limit);
        return $records;
    }

    public static function doSearchQuestions($keyword = NULL, $limit = NULL) {
        $result = DB::table('comlex_level_2_questions')
                ->select(DB::raw('comlex_level_2_questions.*,(select title from comlex_level_1_category where comlex_level_2_questions.category_id = comlex_level_1_category.id ) as category'))
                ->where(function($query) use($keyword) {
                    $query->orWhere('id', 'LIKE', "%$keyword%")
                    ->orWhere('question', 'LIKE', "%$keyword%")
                    ->orWhere('created_at', 'LIKE', "%$keyword%")
                    ->orWhere('updated_at', 'LIKE', "%$keyword%");
                })
                ->orderBy('id', 'desc')
                ->paginate($limit);
        return $result;
    }
    
    public static function doFilterQuestions($keyword = NULL, $d1 = NULL, $d2 = NULL, $category = NULL, $id = NULL, $limit = NULL) {
        
        $query = DB::table('comlex_level_2_questions');
        
        $query->select(DB::raw('comlex_level_2_questions.*,(select title from comlex_level_2_category where comlex_level_2_questions.category_id = comlex_level_2_category.id ) as category, (select type from comlex_level_2_base_questions where comlex_level_2_questions.parent = comlex_level_2_base_questions.id ) as type'));
        
        if(!empty($keyword)) {
            $query->where(function($where_query) use($keyword) {
                $where_query->orWhere('question', 'LIKE', "%$keyword%")
                    ->orWhere('created_at', 'LIKE', "%$keyword%")
                    ->orWhere('updated_at', 'LIKE', "%$keyword%");

            });
        }
        
        if(!empty($id)){
            $query->where(function($where_query) use($id) {
                $where_query->where('id', '=', $id);
            });
        }
        
        if(!empty($d1)){
            $query->where(function($where_query) use($d1) {
                $where_query->where('d1_category_id', '=', $d1);
            });
        }
        
        if(!empty($d2)) {
            $query->where(function($where_query) use($d2) {
                $where_query->where('d2_category_id', '=', $d2);
            });
        }   
        
        if(!empty($category)) {
            $query->where(function($where_query) use($category) {
                $where_query->where('category_id', '=', $category);
            });
        }
        
        $result = $query->orderBy('id', 'desc')->paginate($limit);
        
        return $result;
    }
    
    public static function getUsedQuestionsIdOfUser($user_id = NULL) {
        $data = DB::table('comlex_level_second_exam')
                ->join('comlex_level_second_exam_questions', 'comlex_level_second_exam.id', '=', 'comlex_level_second_exam_questions.exam_id')
                ->where('comlex_level_second_exam.user_id', $user_id)
                ->select('comlex_level_second_exam_questions.question_id')
                ->groupBy('comlex_level_second_exam_questions.question_id')
                ->get();
        $data = json_decode(json_encode($data), true);
        return array_column($data, 'question_id');
    }
    public static function getIncorrectQuestionsIdOfUser($user_id = NULL)
    {
        $data = DB::table('comlex_level_second_exam')
                ->join('comlex_level_second_exam_questions', 'comlex_level_second_exam.id', '=', 'comlex_level_second_exam_questions.exam_id')
                ->where('comlex_level_second_exam.user_id', $user_id)
                ->where('comlex_level_second_exam_questions.is_correct','0')
                 ->where('comlex_level_second_exam_questions.isSubmited','1')
                ->select('comlex_level_second_exam_questions.question_id')
                ->groupBy('comlex_level_second_exam_questions.question_id')
                ->get();
        $data = json_decode(json_encode($data), true);
        return array_column($data, 'question_id');
    }
    
    public static function getMarkQuestionsIdOfUser($user_id = NULL)
    {
        $data = DB::table('comlex_level_second_exam')
                ->join('comlex_level_second_exam_questions', 'comlex_level_second_exam.id', '=', 'comlex_level_second_exam_questions.exam_id')
                ->where('comlex_level_second_exam.user_id', $user_id)
                ->where('comlex_level_second_exam_questions.is_mark','1')
                ->select('comlex_level_second_exam_questions.question_id')
                ->groupBy('comlex_level_second_exam_questions.question_id')
                ->get();
        $data = json_decode(json_encode($data), true);
        return array_column($data, 'question_id');
    }
    
    public static function getUnusedQuestionIdsOfUser($user_id = NULL)
    {
        $ids = self::getUsedQuestionsIdOfUser($user_id);
        $unused_ids  = self::whereNotIn('id',$ids)->select('id')->get()->toArray();
        return $unused_ids;
    }
}
