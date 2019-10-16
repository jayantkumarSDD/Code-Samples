<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
Use App;
Use Request;
Use Validator;
Use Redirect;
Use Hash;
Use App\Models\ComlexLevelFirstQbankCategory;
Use App\Models\ComlexLevelFirstDimensionFirstQbankCategory;
Use App\Models\ComlexLevelFirstDimensionSecondQbankCategory;
Use App\Models\ComlexLevelFirstBaseQuestion;
Use App\Models\ComlexLevelFirstQuestion;


class ComlexLevelFirstController extends Controller {
    

    public function add_update_qbank_category() {
        $input = Request::all();
        $has_id = 0;
        if (isset($input['id'])) {
            $has_id = 1;
        }
        $validator = Validator::make($input, [
            'qbank_category_title' => ['required'],
            'free_trial_allowed_question' => ['numeric'],
            'qbank_category_status' => 'required',
        ]);
        if ($validator->fails()) {
            if ($has_id == 1) {
                return Redirect::to('/admin/comlex_level_first_qbankcategory?id=' . $input['id'])->withErrors($validator);
            } else {
                return Redirect::to('/admin/comlex_level_first_qbankcategory')->withErrors($validator);
            }
        } else {
            $status = ComlexLevelFirstQbankCategory::addUpdateQbankCategory($input);
            if ($status == true) {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_qbankcategory?id=' . $input['id'])->with('message', 'Category updated sucessfully!');
                } else {
                    return Redirect::to('/admin/comlex_level_first_qbankcategory')->with('message', 'Category added sucessfully!');
                }
            } else {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_qbankcategory?id=' . $input['id'])->with('error_message', 'Something went wrong');
                } else {
                    return Redirect::to('/admin/comlex_level_first_qbankcategory')->with('error_message', 'Something went wrong');
                }
            }
        }
    }

    public function showQbankCategoryList() {
        $input = Request::all();
        $input = array_map('trim', $input);
        $categories = ComlexLevelFirstQbankCategory::get_qbank_category_with_child_tr(0, 0, 0);
        return view('admin.comlex_level_first.qbankcategorylist')->with('page_title', 'Qbank  Category  List')->with('categories', $categories);
    }

    public function showQbankCategoryPage() {
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $category = ComlexLevelFirstQbankCategory::getQbankCategoryById($_GET['id']);
            $parent_categories = ComlexLevelFirstQbankCategory::get_qbank_category_with_child(0, 0, $category['parent_id'], $_GET['id']);
            return view('admin.comlex_level_first.qbankcategory')->with('parent_categories', $parent_categories)->with('page_title', ' Add QBank Category')->with('category', $category);
        } else {
            $parent_categories = ComlexLevelFirstQbankCategory::get_qbank_category_with_child(0, 0, 0);
            return view('admin.comlex_level_first.qbankcategory')->with('parent_categories', $parent_categories)->with('page_title', ' Add QBank Category');
        }
    }

    public function showQbankSinglePage() {
        $input = Request::all();
        $input = array_map('trim', $input);
        $baseQuestion = ComlexLevelFirstBaseQuestion::all(['id']);
        $disciplines = ComlexLevelFirstQbankCategory::getDiscipline();
        if (!empty($input) && !empty($input['id'])) {
            $question = ComlexLevelFirstQuestion::find($input['id']);
            $categories = ComlexLevelFirstQbankCategory::get_qbank_category_with_child(0, 0, $question->category_id, 0);
            $d1_categories = ComlexLevelFirstDimensionFirstQbankCategory::get_qbank_category_with_child(0, 0, $question->d1_category_id, 0);
            $d2_categories = ComlexLevelFirstDimensionSecondQbankCategory::get_qbank_category_with_child(0, 0, $question->d2_category_id, 0);
            return view('admin.comlex_level_first.qbanksingle',compact('categories','question','baseQuestion','disciplines','d1_categories','d2_categories'))
                   ->with('page_title', 'Single Answer(Radio)');
                   
        } else {
            $categories = ComlexLevelFirstQbankCategory::get_qbank_category_with_child(0, 0, 0);
            $d1_categories = ComlexLevelFirstDimensionFirstQbankCategory::get_qbank_category_with_child(0, 0, 0);
            $d2_categories = ComlexLevelFirstDimensionSecondQbankCategory::get_qbank_category_with_child(0, 0, 0);
            return view('admin.comlex_level_first.qbanksingle',compact('categories','baseQuestion','disciplines','d1_categories','d2_categories'))
                    ->with('page_title', 'Single Answer(Radio)');
        }
    }

    public function showBaseQuestionPage() {
        $input = Request::all();
        $input = array_map('trim', $input);
        if (!empty($input) && !empty($input['id'])) {
            $question = ComlexLevelFirstBaseQuestion::find($input['id']);
            return view('admin.comlex_level_first.basequestion')->with('page_title', 'Base Question')->with('question', $question);
        } else {
            return view('admin.comlex_level_first.basequestion')->with('page_title', 'Base Question');
        }
    }
    
    public function get_sub_category_by_parentid() {
        $input = Input::all();
        if (empty($input) || empty($input['id'])) {
            echo false;
        } else {
            $categories = QbankSubCategory::get_sub_category_by_parent_id($input['id']);
            if (empty($categories)) {
                echo false;
            } else {
                echo json_encode($categories);
            }
        }
    }

    

    public function add_edit_choice_type_questions() {
        $input = Request::all();
        $has_id = 0;
        if (isset($input['id'])) {
            $has_id = 1;
        } 
        
        $validator = Validator::make($input, [
            'qbank_category' => 'required',
            'd1_qbank_category' => 'required',
            'd2_qbank_category' => 'required',
            'qbank_question' => 'required',
            'qbank_option.*.*'=>'required',
            'qbank_explation' => 'required',
            'qbank_status' => 'required',
        ]);
        if ($validator->fails()) {
            if ($has_id == 1) {
                return Redirect::to('/admin/comlex_level_first_qbanksingle?id=' . $input['id'])->withErrors($validator);
            } else {
                return Redirect::to('/admin/comlex_level_first_qbanksingle')->withErrors($validator);
            }
        } 
        else if (empty(array_filter($input['qbank_correct_answer']))) {
            if ($has_id == 1) {
                return Redirect::to('/admin/comlex_level_first_qbanksingle?id=' . $input['id'])->with('error_message', 'Question correct answer are mendatory');
            } else {
                return Redirect::to('/admin/comlex_level_first_qbanksingle')->with('error_message', 'Question correct answer are mendatory');
            }
        } else {
            
            $exhibit = [];
            if(Request::hasFile('qbank_exhibit')):
                foreach(Request::file('qbank_exhibit') as $key => $file):
                    if (!$file->isValid() || !in_array(strtolower($file->getClientOriginalExtension()), ['jpg','jpeg','png','gif','bmp'])) {
                          return Redirect::to('/admin/comlex_level_first_qbanksingle')->with('error_message', 'Invalid files for exhibit');
                    }
                endforeach;
            endif;
            
            
            if(Request::hasFile('qbank_exhibit')):
                foreach(Request::file('qbank_exhibit') as $key => $file):
                    $destinationPath = 'assets/frontend/images/upload/comlex_level_first';
                    $exhibit[$key] = uploadFile($file,$destinationPath);
                endforeach;
            endif;
            
            $data_exhibit = [];
            if(!empty($input['qbank_exhibit'])):
                foreach($input['qbank_exhibit'] as $key => $value):
                    if(is_object($value)) {
                        $data_exhibit[$key] = $exhibit[$key];
                    } else {
                        $data_exhibit[$key] = $value;
                    }
                     
                endforeach;
            endif;
            
            ksort($data_exhibit);
            $input['exhibit'] = array_values($data_exhibit);
            $status = ComlexLevelFirstQuestion::addUpdateSingleAnswerQuestions($input);
            if ($status == true) {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_qbanksingle?id=' . $input['id'])->with('message', 'Question updated sucessfully!');
                } else {
                    return Redirect::to('/admin/comlex_level_first_qbanksingle')->with('message', 'Question added sucessfully!');
                }
            } else {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_qbanksingle?id=' . $input['id'])->with('error_message', 'Something went wrong');
                } else {
                    return Redirect::to('/admin/comlex_level_first_qbanksingle')->with('error_message', 'Something went wrong');
                }
            }
        }
    }
    
    
    public function add_edit_base_questions() {
        $input = Request::all();
        $validator = Validator::make($input, [
                'qbank_question' => 'required',
                'qbank_type' => 'required'
        ]);
        $has_id = 0;
        if (isset($input['id'])) {
            $has_id = 1;
        }
        if ($validator->fails()) {
            if ($has_id == 1) {
                return Redirect::to('/admin/comlex_level_first_basequestion?id=' . $input['id'])->withErrors($validator);
            } else {
                return Redirect::to('/admin/comlex_level_first_basequestion')->withErrors($validator);
            }
        } else {
            $status = ComlexLevelFirstBaseQuestion::addUpdateQuestion($input);
            if ($status == true) {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_basequestion?id=' . $input['id'])->with('message', 'Question updated sucessfully!');
                } else {
                    return Redirect::to('/admin/comlex_level_first_basequestion')->with('message', 'Question added sucessfully!');
                }
            } else {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_basequestion?id=' . $input['id'])->with('error_message', 'Something went wrong');
                } else {
                    return Redirect::to('/admin/comlex_level_first_basequestion')->with('error_message', 'Something went wrong');
                }
            }
        }
    }
    
    
    public function add_edit_matching_base_questions(){
        $input = Request::all();
        $validator = Validator::make($input, [
                'qbank_question' => 'required',
                'qbank_type' => 'required'
        ]);
        $has_id = 0;
        if (isset($input['id'])) {
            $has_id = 1;
        }
        if ($validator->fails()) {
            if ($has_id == 1) {
                return Redirect::to('/admin/comlex_level_first_matching_basequestion?id=' . $input['id'])->withErrors($validator);
            } else {
                return Redirect::to('/admin/comlex_level_first_matching_basequestion')->withErrors($validator);
            }
        } else {
            $status = ComlexLevelFirstBaseQuestion::addUpdateQuestion($input);
            if ($status == true) {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_matching_basequestion?id=' . $input['id'])->with('message', 'Question updated sucessfully!');
                } else {
                    return Redirect::to('/admin/comlex_level_first_matching_basequestion')->with('message', 'Question added sucessfully!');
                }
            } else {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_matching_basequestion?id=' . $input['id'])->with('error_message', 'Something went wrong');
                } else {
                    return Redirect::to('/admin/comlex_level_first_matching_basequestion')->with('error_message', 'Something went wrong');
                }
            }
        }
    }

    public function showQbankList() {

        $input = Input::all();
        $input = array_map('trim', $input);
        if (empty($input['search'])) {
            $questions = Questions::getAllWithAllAssociated(10);
        } else {
            $result = Questions::doSearchQuestions($input['search']);
            if ($result->isEmpty()) {
                $questions = Questions::getAllWithAllAssociated(10);
            } else {
                $questions = $result;
            }
        }
        return view('admin.questionlist')->with('page_title', 'Question List')->with('questions', $questions);
    }

    public function showBaseQuestionListPage(){
        $input = Request::all();
        $input = array_map('trim', $input);
        if (empty($input['search'])) {
            $questions = ComlexLevelFirstBaseQuestion::getSetsQuestions(10);
        } else {
            $questions = ComlexLevelFirstBaseQuestion::doSearchSetsQuestions($input['search'],10);
        }
        return view('admin.comlex_level_first.basequestionlist')->with('page_title', 'Comlex Level First Set Base Question List')->with('questions', $questions);
    }
    
    public function delete_exhibit_image(){
        $input = Request::all();
        $input = array_map('trim', $input);
        $id = $input['forAction'];
        $key = $input['datarel'];
        $question = ComlexLevelFirstQuestion::find($id);
        if(!empty($question->exhibit)):
            $exhibit = unserialize($question->exhibit);
            $exhibit[$key] = public_path().$exhibit[$key];
            unlink($exhibit[$key]);
            unset($exhibit[$key]);
            $exhibit = array_values($exhibit);
            $question->exhibit = !empty($exhibit) ? serialize($exhibit) : NULL;
            $question->save();
            echo true;
        endif;
    }
    
    public function showSingleQuestionList(){
        $input = Request::all();
        $input = array_map('trim', $input);
        $d1 = '';
        $d2 = '';
        $category = '';
        $searchTerm = '';
        $qId = '';
        if (empty($input['search']) && empty($input['question_id']) && empty($input['d1']) && empty($input['d2']) && empty($input['category'])) {
            $questions = ComlexLevelFirstQuestion::getAllWithAllAssociated(10);
        } else {
            $d1 = $input['d1'];
            $d2 = $input['d2'];
            $category = $input['category'];
            $searchTerm = $input['search'];
            $qId = $input['question_id'];
            $questions = ComlexLevelFirstQuestion::doFilterQuestions($searchTerm, $d1, $d2, $category, $qId, 10);
        }
        
        $mainCategory = ComlexLevelFirstQbankCategory::get_qbank_category_with_child(0,0,$category);
        $dimensionFirstCategory = ComlexLevelFirstDimensionFirstQbankCategory::get_qbank_category_with_child(0, 0, $d1);
        $dimensionSecondCategory = ComlexLevelFirstDimensionSecondQbankCategory::get_qbank_category_with_child(0, 0, $d2);
        return view('admin.comlex_level_first.questionlist', compact('questions', 'dimensionFirstCategory', 'dimensionSecondCategory', 'mainCategory', 'searchTerm', 'd1', 'd2', 'qId', 'category'))->with('page_title', 'Comlex Level First Question List');
    }
    
    public function showMatchingBaseQuestion(){
        $input = Request::all();
        $input = array_map('trim', $input);
        if (!empty($input) && !empty($input['id'])) {
            $question = ComlexLevelFirstBaseQuestion::find($input['id']);
            return view('admin.comlex_level_first.matching_base_question')->with('page_title', 'Matching Base Question')->with('question', $question);
        } else {
            return view('admin.comlex_level_first.matching_base_question')->with('page_title', 'Matching Base Question');
        }
    }
    
    public function showMatchingBaseQuestionList(){
        $input = Request::all();
        $input = array_map('trim', $input);
        if (empty($input['search'])) {
            $questions = ComlexLevelFirstBaseQuestion::getMatchingSetsQuestions(10);
        } else {
            $questions = ComlexLevelFirstBaseQuestion::doSearchMatchingSetsQuestions($input['search'],10);
        }
        return view('admin.comlex_level_first.matching_sets_base_question_list')->with('page_title', 'Comlex Level First Matching Set Base Question List')->with('questions', $questions);
    }
}
