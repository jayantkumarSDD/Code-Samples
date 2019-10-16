<?php

namespace App\Http\Controllers\Front;

Use App\Http\Controllers\Controller;
Use Request;
Use Validator;
Use Redirect;
Use App\Models\ComlexLevelSecondBaseQuestion;
Use App\Models\ComlexLevelSecondQuestion;
Use App\Models\ComlexLevelSecondQbankCategory;
Use App\Models\ComlexLevelSecondExam;
Use App\Models\ComlexLevelSecondExamQuestions;
use App\Models\ComlexLevelSecondQuestionsComment;
use App\Models\GenerateComlexLevelSecondTestCategoriesDiscipline;
use Mail;
Use DB;

class ComlexLevelSecondController extends Controller {
    
    use \App\Traits\ComlexLevelSecondPerformanceAnalysisTrait;
    
    public function showQbankCategories() {
        $categories = ComlexLevelSecondQbankCategory::getQbankCategories();
        $categories = $this->getCategoriesQuestionsModeWise($categories);
        $allowed_free_trial_questions = \Config::get('free_trial.allowed_free_trial_questions');
        $totalUsedQuestion = $this->getCountUsedQuestionOfUser(\Auth::user()->id);
        return View('/qbank/comlex_level_second/qbank_category', compact('categories','allowed_free_trial_questions','totalUsedQuestion'))->with('page_title', ' COMLEX Level 2-CE Qbank');
    }

    public function getCategoriesQuestionsModeWise($categories) {
        $data = [];
        if (!empty($categories)):

            $user_id = \Auth::user()->id;
            $usedQuestionIds = ComlexLevelSecondQuestion::getUsedQuestionsIdOfUser($user_id);
            $markedQuestionIds = ComlexLevelSecondQuestion::getMarkQuestionsIdOfUser($user_id);
            $incorrectQuestionIds = ComlexLevelSecondQuestion::getIncorrectQuestionsIdOfUser($user_id);


            foreach ($categories as $category):
                $category_id = $category['category_id'];
                $category['mode']['all'] = ComlexLevelSecondQuestion::where('category_id', $category_id)
                        ->where('status', 'Enabled')
                        ->count();


                $category['mode']['new'] = ComlexLevelSecondQuestion::where('category_id', $category_id)
                        ->whereNotIn('id', $usedQuestionIds)
                        ->where('status', 'Enabled')
                        ->count();

                $category['mode']['marked'] = ComlexLevelSecondQuestion::where('category_id', $category_id)
                        ->whereIn('id', $markedQuestionIds)
                        ->where('status', 'Enabled')
                        ->count();

                $category['mode']['incorrect'] = ComlexLevelSecondQuestion::where('category_id', $category_id)
                        ->whereIn('id', $incorrectQuestionIds)
                        ->where('status', 'Enabled')
                        ->count();

                $category['mode']['used'] = ComlexLevelSecondQuestion::where('category_id', $category_id)
                        ->whereIn('id', $usedQuestionIds)
                        ->where('status', 'Enabled')
                        ->count();
                $data[] = $category;
            endforeach;
        endif;
        return $data;
    }

    public function getTotalCountQuestionByCatDiscipline() {
        $data = Request::all();
        $categories = $data['categories'];
        $disciplines = $data['discipline'];
        $result = ComlexLevelSecondQuestion::whereIn('category_id', $categories)
                ->whereIn('discipline', $disciplines)
                ->count();
        return $result;
    }

    public function resumeTest($test_id = NULL) {
        $user_id = \Auth::user()->id;
        $exam_data = ComlexLevelSecondExam::with('exam_question')
                        ->where('id', $test_id)
                        ->where('user_id', $user_id)
                        ->where(function ($query) {
                            $query->where('submit_type', 'paused')
                                  ->orWhereNull('submit_type');
                        })
                        ->first();
           
        if (!empty($exam_data)) {
            $exam_data = $exam_data->toArray();
            $test_mode = $exam_data['test_mode'];
            $total_time_spent = $exam_data['total_time_spent'];
            $no_of_question = count($exam_data['exam_question']);
            $last_resume_index = $exam_data['last_resume_index'];

            $questions = [];
            $exam_question_data = [];
            foreach ($exam_data['exam_question'] as $exam_question):
                $questions[] = $exam_question['question_id'];
                $exam_question_data[$exam_question['question_id']] = $exam_question;
            endforeach;
            $implode_questions = implode(',', $questions);
            $filtered_questions = ComlexLevelSecondQuestion::whereIn('id', $questions)
                            ->orderBy(DB::raw("FIELD(id, $implode_questions)"))
                            ->get()->toArray();
            $questions_data = [];
            foreach ($filtered_questions as $question):
                if (!empty($question['parent'])) {
                    $base_question = ComlexLevelSecondBaseQuestion::where('id', $question['parent'])->first();
                    $main_question = $question['question'];
                    $main_options = $question['options'];
                    $question['question'] = $base_question['question'];
                    $question['options'] = $base_question['options'];
                    $question['subset_question'] = $main_question;
                    $question['subset_options'] = $main_options;
                }
                $id = $question['id'];
                if ($test_mode == 'tutor') {
                    $question['correct_answer'] = unserialize($question['correct_answer']);
                } else {
                    unset($question['correct_answer']);
                    unset($question['explanation']);
                }


                $question['options'] = unserialize($question['options']);
                if (!empty($question['subset_options'])):
                    $question['subset_options'] = unserialize($question['subset_options']);
                endif;
                $question['exhibit'] = !empty($question['exhibit']) ? unserialize($question['exhibit']) : $question['exhibit'];
                
                $question['status'] = $exam_question_data[$id]['status'];


                $question['isSubmited'] = $exam_question_data[$id]['isSubmited'];


                $question['timeSpent'] = $exam_question_data[$id]['timeSpent'];


                $question['is_mark'] = $exam_question_data[$id]['is_mark'];


                $question['is_correct'] = $exam_question_data[$id]['is_correct'];
                
                if (!empty($exam_question_data[$id]['isDisabled'])):
                    $question['isDisabled'] = $exam_question_data[$id]['isDisabled'];
                endif;
                if (!empty($exam_question_data[$id]['selected'])):
                    $question['selected'] = $exam_question_data[$id]['selected'];
                endif;

                if (!empty($question['parent'])):
                    $question['question_count'] = $exam_question_data[$id]['question_count'];
                    $question['position'] = $exam_question_data[$id]['position'];
                endif;
                $questions_data[] = $question;

            endforeach;

            $response['test_mode'] = $test_mode;
            $response['total_time_spent'] = $total_time_spent;
            $response['test_id'] = $test_id;
            $response['questions'] = $questions_data;
            $response['last_resume_index'] = $last_resume_index;

            $response = json_encode($response);
            return View('qbank/comlex_level_second/exam')->with('page_title', 'Comlex Qbank')->with('data', $response);
        } else {

            return Redirect::to('/student/comlex-level-2-review-exams');
        }
    }
    
    private function getFilteredQuestionsByMode($question_mode,$categories,$no_of_question,$user_id){
        $filtered_questions = [];
        if($question_mode == 'all')
        {
            $filtered_questions = ComlexLevelSecondQuestion::whereIn('category_id', $categories)
                            ->where('status', 'Enabled')
                            ->limit($no_of_question)
                            ->get()->toArray();
        }
        else if($question_mode == 'new')
        {
            $questionsIds = $this->getUsedQuestionIdsOfUser($user_id);
            $filtered_questions = ComlexLevelSecondQuestion::whereIn('category_id', $categories)
                            ->whereNotIn('id',$questionsIds)
                            ->where('status', 'Enabled')
                            ->limit($no_of_question)
                            ->get()->toArray();
        }
        else if($question_mode == 'marked')
        {
            $questionsIds = $this->getMarkedQuestionIdsOfUser($user_id);
            $filtered_questions = ComlexLevelSecondQuestion::whereIn('category_id', $categories)
                            ->whereIn('id',$questionsIds)
                            ->where('status', 'Enabled')
                            ->limit($no_of_question)
                            ->get()->toArray();
        }
        else if($question_mode == 'incorrect')
        {
            $questionsIds = $this->getIncorrectQuestionIdsOfUser($user_id);
            $filtered_questions = ComlexLevelSecondQuestion::whereIn('category_id', $categories)
                            ->whereIn('id',$questionsIds)
                            ->where('status', 'Enabled')
                            ->limit($no_of_question)
                            ->get()->toArray();
        }
        else if($question_mode == 'used')
        {
            $questionsIds = $this->getUsedQuestionIdsOfUser($user_id);
            $filtered_questions = ComlexLevelSecondQuestion::whereIn('category_id', $categories)
                            ->whereIn('id',$questionsIds)
                            ->where('status', 'Enabled')
                            ->limit($no_of_question)
                            ->get()->toArray();
        }
        return $filtered_questions;
    }
    
    public function takeTest($test_id) {
        $user_id = \Auth::user()->id;
        $exam_data = ComlexLevelSecondExam::with('exam_question')
                ->where('id', $test_id)
                ->where('user_id', $user_id)
                ->first();
        if ($exam_data->submit_type == 'paused') {
            return Redirect::to('/level2/resume_exam/' . $test_id);
        } else if ($exam_data->submit_type == 'complete') {
            return Redirect::to('/level2/review_exam/' . $test_id);
        } else {
            $test_data = GenerateComlexLevelSecondTestCategoriesDiscipline::where('exam_id', $test_id)->first()->toArray();
            $categories = explode(',', $test_data['categories']);
            $no_of_question = $test_data['no_of_questions'];
            $test_mode = $exam_data->test_mode;
            $last_resume_index = $exam_data->last_resume_index;
            $question_mode = $exam_data->question_mode;
            
            $filtered_questions = $this->getFilteredQuestionsByMode($question_mode,$categories,$no_of_question,$user_id);
            
            $questions_data = [];
            $exist_question = [];

            $check_count_question = 0;
            foreach ($filtered_questions as $question):
                if ($check_count_question < $no_of_question):
                    if (!in_array($question['id'], $exist_question)):
                        if ($question['parent'] == 0) {
                            $question['question_count'] = 1;
                            $questions_data[] = $question;
                            $exist_question[] = $question['id'];
                            $check_count_question += 1;
                        } else {
                            $set_of_questions = ComlexLevelSecondQuestion::where('parent', $question['parent'])->where('status', 'Enabled')->get()->toArray();
                            if (!empty($set_of_questions)):
                                $base_question = ComlexLevelSecondBaseQuestion::where('id', $question['parent'])->first();
                                $sub_set_question_count = 1;
                                $base_question_count = $set_of_questions[0]['question_count'];
                                $set_question_count = 0;
                                if ($base_question_count <= $no_of_question - count($exist_question)) {
                                    $set_question_count = $base_question_count;
                                } else {
                                    $set_question_count = $no_of_question - count($exist_question);
                                }
                                foreach ($set_of_questions as $key => $set_question):
                                    if ($check_count_question < $no_of_question):
                                        $main_question = $set_question['question'];
                                        $main_options = $set_question['options'];
                                        $set_question['question'] = $base_question['question'];
                                        $set_question['options'] = $base_question['options'];
                                        $set_question['subset_question'] = $main_question;
                                        $set_question['subset_options'] = $main_options;
                                        $set_question['position'] = $key + 1;
                                        $set_question['question_count'] = $set_question_count;
                                        $questions_data[] = $set_question;
                                        $exist_question[] = $set_question['id'];
                                        $check_count_question += 1;
                                        $sub_set_question_count += 1;
                                    endif;
                                endforeach;
                            endif;
                        }
                    //$check_count_question += $question['question_count'];
                    endif;
                endif;
            endforeach;

            //set questions
            $questions = [];
            $exam_questions = [];

            foreach ($questions_data as $data):
                if ($test_mode == 'tutor') {
                    $data['correct_answer'] = unserialize($data['correct_answer']);
                } else {
                    unset($data['correct_answer']);
                    unset($data['explanation']);
                }
                $data['options'] = unserialize($data['options']);
                if (!empty($data['subset_options'])):
                    $data['subset_options'] = unserialize($data['subset_options']);
                endif;
                $data['exhibit'] = !empty($data['exhibit']) ? unserialize($data['exhibit']) : $data['exhibit'];
                $questions[] = $data;
                $exam_questions[] = [
                    'exam_id' => $test_id,
                    'question_id' => $data['id'],
                    'position' => !empty($data['parent']) ? $data['position'] : NULL,
                    'question_count' => !empty($data['parent']) ? $data['question_count'] : NULL
                ];

            endforeach;
            ComlexLevelSecondExamQuestions::insert($exam_questions);
            $response['test_mode'] = $test_mode;
            $response['test_id'] = $test_id;
            $response['questions'] = $questions;
            $response['last_resume_index'] = $last_resume_index;
            $response = json_encode($response);
            return View('qbank/comlex_level_second/exam')->with('page_title', 'Comlex Qbank')->with('data', $response);
        }
    }
    
    private function validateAccess($vars){
        $data = [];
        if(\Auth::user()->is_free_trial_user && !\Auth::user()->hasOMMAccess){
            $allowed_free_trial_questions = \Config::get('free_trial.allowed_free_trial_questions');
            $usedQuestion = $this->getCountUsedQuestionOfUser(\Auth::user()->id);
            $allowed_questions = $allowed_free_trial_questions - $usedQuestion; 
            if($vars['no_of_question']>$allowed_questions && in_array($vars['question_mode'],['All','New'])){
                $data['error_type'] = 'free_trial_qbank';
                $data['status'] = 'failure';
                $data['allowed_free_trial_questions'] = $allowed_free_trial_questions;
                $data['used_questions'] = $usedQuestion;
            } else {
                $data['status'] = 'success';
            }
        }
        else if(\Auth::user()->is_free_trial_user && \Auth::user()->hasOMMAccess){
            $allowed_omm_questions = ComlexLevelSecondQbankCategory::join('comlex_level_2_questions', 'comlex_level_2_category.id', '=', 'comlex_level_2_questions.category_id')
                                            ->where('comlex_level_2_category.status','=','Enabled')
                                            ->where('comlex_level_2_category.is_omm_category','=','yes')
                                            ->where('comlex_level_2_questions.status','=','Enabled')
                                            ->count();
            $usedQuestion = $this->getCountUsedQuestionOfUser(\Auth::user()->id)-\Config::get('free_trial.allowed_free_trial_questions');
            if($vars['question_mode'] == 'New'){
                $allowed_questions = $allowed_omm_questions - $usedQuestion; 
            } else {
                $allowed_questions = $allowed_omm_questions;
            }
            
            if($vars['no_of_question']>$allowed_questions && in_array($vars['question_mode'],['All','New'])){
                $data['error_type'] = 'free_trial_qbank';
                $data['status'] = 'failure';
                $data['allowed_free_trial_questions'] = $allowed_omm_questions;
                $data['used_questions'] = $usedQuestion;
            } else {
                $data['status'] = 'success';
            }
        }
        else if(!\Auth::user()->membership_access['level_2_qbank']['hasAccess']){
            $data['error_type'] = 'qbank_membership_expired';
            $data['status'] = 'failure';
        }
        else {
            $data['status'] = 'success';
        }
        return $data;
    }
    
    public function createTest() {
        $user_id = \Auth::user()->id;
        $input = Request::all();
        $access = $this->validateAccess($input);
        if($access['status'] == 'failure'){
            return redirect()->to('membership_notification')->with('error_type', $access['error_type'])->with('data',$access);
        }
        $no_of_question = $input['no_of_question'];
        $test_mode = $input['test_mode'];
        $question_mode = $input['question_mode'];

        /*         * ********************************************* */
        $comlexLevelSecondExam = new ComlexLevelSecondExam;
        $comlexLevelSecondExam->user_id = $user_id;
        $comlexLevelSecondExam->test_mode = $test_mode;
        $comlexLevelSecondExam->question_mode = $question_mode;
        $exam = $comlexLevelSecondExam->save();
        /*         * ********************************************* */

        $generateTestCategoriesDiscipline = new GenerateComlexLevelSecondTestCategoriesDiscipline;
        $generateTestCategoriesDiscipline->exam_id = $comlexLevelSecondExam->id;
        $generateTestCategoriesDiscipline->categories = implode(',', $input['category']);
//        $generateTestCategoriesDiscipline->discipline = implode(',', $input['discipline']);
        $generateTestCategoriesDiscipline->no_of_questions = $no_of_question;
        $generateTestCategoriesDiscipline->save();

        return Redirect::to('/level2/take/test/' . $comlexLevelSecondExam->id);
    }

    public function saveComment() {
        $input = Request::all();
        $user_id = \Auth::user()->id;
        $questionsComment = new ComlexLevelSecondQuestionsComment;
        $questionsComment->user_id = $user_id;
        $questionsComment->question_id = $input['question_id'];
        $questionsComment->comment_type = $input['comment_type'];
        $questionsComment->comment = $input['comment'];
        $questionsComment->save();
        $this->sendCommentToAdmin($input['question_id'], $input['comment_type'], $input['comment']);
    }

    public function sendCommentToAdmin($q_id = NULL, $comment_type = NULL, $comment = NULL) {
        $user = getLoggedInUserData();
        if (!empty($user)) {
            $data['name'] = $user['full_name'];
            $data['email'] = $user['email'];
            $data['q_id'] = $q_id;
            $data['comment_type'] = getCommentType($comment_type);
            $data['comment'] = $comment;
            try {
                $status = Mail::queue('emails.comments', $data, function($message) {
                            $message->to('tomiwa007@gmail.com', 'Adeleke Adesina')->subject('OMT Comlex Level 2 Qbank Comments');
                            $message->bcc('ankitgpt222@gmail.com', 'Ankit Gupta');
                        });
            } catch (Exception $e) {
                
            }
        }
        return true;
    }

    public function saveTest() {
        $input = Request::all();
        $examData = $input['testData'];
        if (!empty($examData)) {
            $examData = json_decode($examData, true);
            $questions = $examData['questions'];
            $exam_id = $examData['test_id'];
            $last_resume_index = $examData['last_resume_index'];
            $total_time = 0;
            $submit_type = !empty($examData['submit_type']) ? $examData['submit_type'] : 'paused';
            foreach ($questions as $key => $value):
                $update = [];

                if (isset($value['selected'])):
                    $update['selected'] = $value['selected'];
                endif;

                if (isset($value['is_mark'])):
                    $update['is_mark'] = $value['is_mark'];
                endif;

                if (isset($value['isDisabled'])):
                    $update['isDisabled'] = $value['isDisabled'];
                endif;

                if (isset($value['is_correct'])):
                    $update['is_correct'] = $value['is_correct'];
                endif;
                
                if (isset($value['isSubmited'])):
                    $update['isSubmited'] = $value['isSubmited'];
                endif;
                
                $update['timeSpent'] = $value['timeSpent'];
                $total_time += $value['timeSpent'];
                ComlexLevelSecondExamQuestions::where('exam_id', $exam_id)
                        ->where('question_id', $key)
                        ->update($update);
            endforeach;
            $comlexLevelSecondExam = ComlexLevelSecondExam::find($exam_id);
            $comlexLevelSecondExam->total_time_spent = $total_time;
            $comlexLevelSecondExam->submit_type = $submit_type;
            $comlexLevelSecondExam->last_resume_index = $last_resume_index;
            $comlexLevelSecondExam->save();
            return true;
        }
    }

    public function submitTest() {
        $this->saveTest();
        return Redirect::to('/student/comlex-level-2-review-exams')->with('page_title','Review My Exam');
    }

    public function apiSubmitTest() {
        $this->saveTest();
        $response['status'] = 'success';
        $response['status_code'] = 200;
        $response['message'] = "Exam data has been saved successfully";
        return $response;
    }

    public function reviewExams() {
        $user_id = \Auth::user()->id;
        $exams = ComlexLevelSecondExam::with('exam_question')
                ->where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->paginate(10);

        foreach ($exams as $exam):
            if (!$exam->exam_question->isEmpty()):
                $category = [];
                $right_answered_question = [];
                foreach ($exam->exam_question as $exam_question):
                    $question = ComlexLevelSecondQuestion::find($exam_question->question_id);
                    
                    if (!empty($question) && !in_array($question->category_id, $category)):
                        $category[] = $question->category_id;
                    endif;

                    if ($exam_question->is_correct == 1):
                        $right_answered_question[] = $exam_question->question_id;
                    endif;

                endforeach;
                if (count($category) > 1) {
                    $exam->category = 'Multiple';
                } else {
                    $category = ComlexLevelSecondQbankCategory::find($category[0]);
                    $exam->category = ucfirst($category->title);
                }
                $right_answered_question = count($right_answered_question);
                $exam->score = $this->calculateScore($right_answered_question, $exam->exam_question->count());
                $exam->right_answered_question = $right_answered_question;
            endif;
        endforeach;
        return View('user.review_exams')->with('exams', $exams)->with('page_title','Review My Exam (COMLEX Level 2-CE)');
    }

    public function calculateScore($count_answered_question, $exam_question_count) {
        return round(($count_answered_question * 100) / $exam_question_count);
    }

    public function reviewTest($test_id) {
        $user_id = \Auth::user()->id;
        $exam_data = ComlexLevelSecondExam::with('exam_question')
                ->where('id', $test_id)
                ->where('user_id', $user_id)
                ->where('submit_type', 'complete')
                ->first();

        if (!empty($exam_data)) {
            $exam_data = $exam_data->toArray();
            $test_mode = $exam_data['test_mode'];
            $total_time_spent = $exam_data['total_time_spent'];
            $no_of_question = count($exam_data['exam_question']);

            $questions = [];
            $exam_question_data = [];


            foreach ($exam_data['exam_question'] as $exam_question):
                $questions[] = $exam_question['question_id'];
                $exam_question_data[$exam_question['question_id']] = $exam_question;
            endforeach;

            $implode_questions = implode(',', $questions);
            $filtered_questions = ComlexLevelSecondQuestion::whereIn('id', $questions)
                            ->orderBy(DB::raw("FIELD(id, $implode_questions)"))
                            ->get()->toArray();

            $questions_data = [];

            foreach ($filtered_questions as $question):
                if (!empty($question['parent'])) {
                    $base_question = ComlexLevelSecondBaseQuestion::where('id', $question['parent'])->first();
                    $main_question = $question['question'];
                    $main_options = $question['options'];
                    $question['question'] = $base_question['question'];
                    $question['options'] = $base_question['options'];
                    $question['subset_question'] = $main_question;
                    $question['subset_options'] = $main_options;
                }

                $id = $question['id'];

                $question['correct_answer'] = unserialize($question['correct_answer']);

                $question['options'] = unserialize($question['options']);
                if (!empty($question['subset_options'])):
                    $question['subset_options'] = unserialize($question['subset_options']);
                endif;
                $question['exhibit'] = !empty($question['exhibit']) ? unserialize($question['exhibit']) : $question['exhibit'];
                if (!empty($exam_question_data[$id]['status'])):
                    $question['status'] = $exam_question_data[$id]['status'];
                endif;
                //if (!empty($exam_question_data[$id]['isSubmited'])):
                    $question['isSubmited'] = $exam_question_data[$id]['isSubmited'];
                //endif;
                //if (!empty($exam_question_data[$id]['timeSpent'])):
                    $question['timeSpent'] = $exam_question_data[$id]['timeSpent'];
                //endif;
                //if (!empty($exam_question_data[$id]['is_mark'])):
                    $question['is_mark'] = $exam_question_data[$id]['is_mark'];
                //endif;
                //if (!empty($exam_question_data[$id]['is_correct'])):
                    $question['is_correct'] = $exam_question_data[$id]['is_correct'];
                //endif;
                if (!empty($exam_question_data[$id]['isDisabled'])):
                    $question['isDisabled'] = $exam_question_data[$id]['isDisabled'];
                endif;
                if (!empty($exam_question_data[$id]['selected'])):
                    $question['selected'] = $exam_question_data[$id]['selected'];
                endif;

                if (!empty($question['parent'])):
                    $question['question_count'] = $exam_question_data[$id]['question_count'];
                    $question['position'] = $exam_question_data[$id]['position'];
                endif;
                $questions_data[] = $question;

            endforeach;
            $response['test_mode'] = 'review';
            $response['total_time_spent'] = $total_time_spent;
            $response['test_id'] = $test_id;
            $response['questions'] = $questions_data;
            $response['submitType'] = $exam_data['submit_type'];
            $response['last_resume_index'] = $exam_data['last_resume_index'];
            $response = json_encode($response);
            return View('qbank/comlex_level_second/exam')->with('page_title', 'Comlex Qbank')->with('data', $response);
        }
        else {
            return Redirect::to('/student/comlex-level-2-review-exams')->with('page_title','Review My Exam');
        }
    }
    
    public function showDashboard() {
        $user_id = \Auth::user()->id;
        $usedQuestionCount = $this->getCountUsedQuestionOfUser($user_id);
        $unusedQuestionCount = $this->getCountUnusedQuestionOfUser($user_id);
        $correctQuestionCount = $this->getCountCorrectQuestionOfUser($user_id);
        $incorrectQuestionCount = $this->getCountIncorrectQuestionOfUser($user_id);
        $incompleteQuestionCount = $this->getCountIncompleteQuestionOfUser($user_id);
        $userTestProgress = $this->getScoreProgressOfUser($user_id);
        $answerChangedStatus = $this->getAnswerChangedStatusOfUser($user_id); 
        return View('/user/dashboard',  compact('userTestProgress','unusedQuestionCount','usedQuestionCount','correctQuestionCount', 'incorrectQuestionCount', 'incompleteQuestionCount', 'answerChangedStatus'))->with('page_title','Dashboard')->with('body_class','dashboard');
    }

}
