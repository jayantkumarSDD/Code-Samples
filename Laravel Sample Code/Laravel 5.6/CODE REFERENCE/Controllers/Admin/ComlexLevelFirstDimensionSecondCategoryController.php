<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
Use App;
Use App\Models\ComlexLevelFirstDimensionSecondQbankCategory;
Use Illuminate\Http\Request;
Use Validator; 
Use Redirect;

class ComlexLevelFirstDimensionSecondCategoryController extends Controller {
    
    public function showQbankCategoryPage(Request $request) {
        if ($request->has('id') && !empty($request->input('id'))) {
            $category = ComlexLevelFirstDimensionSecondQbankCategory::getQbankCategoryById($_GET['id']);
            $parent_categories = ComlexLevelFirstDimensionSecondQbankCategory::get_qbank_category_with_child(0, 0, $category['parent_id'], $_GET['id']);
            return view('admin.comlex_level_first.dimension_second_qbank_category',compact('parent_categories','category'))->with('page_title', ' Add Dimesion Second QBank Category');
        } else {
            $parent_categories = ComlexLevelFirstDimensionSecondQbankCategory::get_qbank_category_with_child(0, 0, 0);
            return view('admin.comlex_level_first.dimension_second_qbank_category',compact('parent_categories'))->with('page_title', ' Add Dimesion Second QBank Category');
        }
    }
    
    public function add_update_qbank_category(Request $request){
        $input = $request->all();
        $has_id = 0;
        if (isset($input['id'])) {
            $has_id = 1;
        } 
        
        $validator = Validator::make($input, [
            'qbank_category_title' => ['required'],
            'qbank_category_status' => 'required'
        ]);
        
        if ($validator->fails()) {
            if ($has_id == 1) {
                return Redirect::to('/admin/comlex_level_first_d2_qbankcategory?id=' . $input['id'])->withErrors($validator);
            } else {
                return Redirect::to('/admin/comlex_level_first_d2_qbankcategory')->withErrors($validator);
            }
        } else {
            $status = ComlexLevelFirstDimensionSecondQbankCategory::addUpdateQbankCategory($input);
            if ($status == true) {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_d2_qbankcategory?id=' . $input['id'])->with('message', 'Category updated sucessfully!');
                } else {
                    return Redirect::to('/admin/comlex_level_first_d2_qbankcategory')->with('message', 'Category added sucessfully!');
                }
            } else {
                if ($has_id == 1) {
                    return Redirect::to('/admin/comlex_level_first_d2_qbankcategory?id=' . $input['id'])->with('error_message', 'Something went wrong');
                } else {
                    return Redirect::to('/admin/comlex_level_first_d2_qbankcategory')->with('error_message', 'Something went wrong');
                }
            }
        }
    }
    
    public function showQbankCategoryList(){
        $categories = ComlexLevelFirstDimensionSecondQbankCategory::get_qbank_category_with_child_tr(0, 0, 0);
        return view('admin.comlex_level_first.dimension_second_qbank_category_list',  compact('categories'))->with('page_title', 'Dimension Second Qbank Category List');
    }
    
    
    
}
