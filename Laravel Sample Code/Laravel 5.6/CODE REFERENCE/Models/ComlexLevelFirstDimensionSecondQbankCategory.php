<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
Use DB;

class ComlexLevelFirstDimensionSecondQbankCategory extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'comlex_level_first_d2_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title','parent_id','status'];

    public static function addUpdateQbankCategory($vars) {
        $cat = Self::firstOrNew(['id' => isset($vars['id']) ? $vars['id'] : '']);
        $cat->parent_id = !empty($vars['parent_category']) ? $vars['parent_category'] : 0;
        $cat->title = $vars['qbank_category_title'];
        $cat->status = $vars['qbank_category_status'];
        $status = $cat->save();
        return $status;
    }

    public static function doSearch($keyword) {
        $result = Self::orWhere('id', 'LIKE', "%$keyword%")
                ->orWhere('title', 'LIKE', "%$keyword%")
                ->orWhere('created_at', 'LIKE', "%$keyword%")
                ->orWhere('updated_at', 'LIKE', "%$keyword%")
                ->get();
        return $result;
    }

    public static function getAll($limit = null) {
        $data = DB::table('qbank_category')
                ->orderBy('id', 'DESC')
                ->paginate($limit);
        return $data;
        //return QbankCategory::with('parents')->orderBy('id', 'DESC')->paginate($limit) ;
    }

    public static function get_all_parent_categories() {
        $parent_categories = Self::where('parent_id',0)
                             ->where('status','Enabled')   
                             ->get(['id','title']);
        return $parent_categories;
    }
    
    public static function get_all_parent_categories_with_question_count() {
        $parent_categories = Self::where('parent_id',0)
                             ->where('status','Enabled')
                             ->select(DB::raw("(select count(comlex_level_1_questions.id) from comlex_level_1_questions WHERE comlex_level_1_questions.category_id = comlex_level_1_category.id group by comlex_level_1_questions.category_id ) AS questions"),'id','title')
                             ->get();
        return $parent_categories;
    }
    
    public static function getQbankCategoryById($id) {
        return Self::find($id)->toArray();
    }

    public static function doSearchQbankCategory($keyword = null) {
        $result = DB::table('qbank_category')
                ->where(function($query) use($keyword) {
                    $query->orWhere('qbank_category.id', 'LIKE', "%$keyword%")
                    ->orWhere('qbank_category.title', 'LIKE', "%$keyword%")
                    ->orWhere('qbank_category.created_at', 'LIKE', "%$keyword%")
                    ->orWhere('qbank_category.updated_at', 'LIKE', "%$keyword%");
                })
                ->paginate(10);

        return $result;
    }

    

    public static function get_qbank_category_with_child($id = 0, $pass = 0, $selected = 0, $exclude_id = null, $parentTitle = 'Parent Category') {
        $cats = Self::where('parent_id', '=', $id)->get();
        $html = '';
        if (!$cats->isEmpty()) {
            foreach ($cats as $val):
                if ($val->id != $exclude_id):
                    $selected_text = '';
                    if ($val->id == $selected):
                        $selected_text = 'selected';
                    endif;
                    $html .= '<option value="' . $val->id . '" ' . $selected_text . '>';
                    $html .= str_repeat("--", $pass);
                    $html .= $val->title;
                    $html .= ' ['.$parentTitle.']</option>';
                    $html .= Self::get_qbank_category_with_child($val->id, $pass + 1, $selected, $exclude_id, $val->title);
                endif;
            endforeach;
        }
        return $html;
    }

    public static function get_qbank_category_with_child_array($id = 0, $pass = 0, $selected = 0, $exclude_id = null) {
        $cats = Self::where('parent_id', '=', $id)->get()->toArray();
        $category = array();
        if (!empty($cats)) {
            foreach ($cats as $key => $val):

                $category[$key] = $val;

                $category[$key]['child'] = Self::get_qbank_category_with_child_array($val['id'], $pass + 1, $selected, $exclude_id);

            endforeach;
        }
        return $category;
    }

    public static function get_qbank_category_with_child_ids($id = 0, $pass = 0, $selected = 0, $exclude_id = null) {
        $cats = Self::where('parent_id', '=', $id)->where('status', '=', 'Enabled')->get()->toArray();
        $category = '';
        $category .= $id . ',';
        if (!empty($cats)) {
            foreach ($cats as $key => $val):

                $category .= $val['id'] . ',';

                $category .= Self::get_qbank_category_with_child_ids($val['id'], $pass + 1, $selected, $exclude_id);

            endforeach;
        }
        return $category;
    }

    

    public static function getParentNameByCategory($multi_id = NULL) {
        $data = Self::whereIn('id', $multi_id)->get()->toArray();
        $cat = array_column($data, 'parent_id');
        $cat = array_unique($cat);
        $return = '';
        if (count($cat) == 1 && $cat[0] == 0) {

            $category = QbankCategory::find($data[0]['id']);

            $return .= $category->title;
        } else if (count($cat) == 1 && $cat[0] != 0) {
            $return .= Self::getParentNameByCategory($cat);
        } else {
            $return .= 'Multiple';
        }


        return $return;
    }

    public static function getParentIdsByCategoryIdsArray($ids = NULL) {
        $data = Self::whereIn('id', $ids)->get();

        $return = '';
        if (!empty($data)):
            foreach ($data as $key => $value):
                $return .= $value->id . ',';
                if ($value->parent_id != 0) {
                    $return .= Self::getParentIdsByCategoryIdsArray(array($value->parent_id));
                }
            endforeach;
        endif;
        return $return;
    }

    public static function getParentIdsByCategoryIdArray($ids = NULL) {
        $data = QbankCategory::where('id', $ids)->get();

        $return = '';
        if (!empty($data)):
            foreach ($data as $key => $value):
                $return .= $value->id . ',';
                if ($value->parent_id != 0) {
                    $return .= Self::getParentIdsByCategoryIdsArray(array($value->parent_id));
                }
            endforeach;
        endif;
        return $return;
    }

    public static function getParentNameByCategoryArray($id = NULL) {
        $data = QbankCategory::find($id);
        $return = [];
        $return [] = isset($data->title) ? $data->title : '';
        if ($data->parent_id != 0) {
            $return [] = Self::getParentNameByCategoryArray($data->parent_id);
        }

        return $return;
    }

    public static function getParentNameByCategoryIdWithoutSelf($id = NULL) {
        $data = Self::find($id);
        $return = [];
        if ($id !== $data->id):
            $return [$data->parent_id] = isset($data->title) ? $data->title : '';
        endif;
        if ($data->parent_id != 0) {
            $return [$data->parent_id] = Self::getParentNameByCategoryArray($data->parent_id);
        }

        return $return;
    }

    public static function get_qbank_category_with_child_tr($id = 0, $pass = 0, $selected = 0, $exclude_id = null) {
        $cats = Self::where('parent_id', '=', $id)->get();
        $html = '';
        if (!$cats->isEmpty()) {
            foreach ($cats as $val):

                $checked = '';
                if (isset($val->status) && $val->status == 'Enabled'):
                    $checked = 'checked';
                endif;
                $html .= '<tr>';
                $html .= '<td>' . $val->id . '</td>';
                $html .= '<td>' . str_repeat("&nbsp;=>", $pass) . $val->title . '</td>'; // use the $pass value to create the --
                $html .= '<td>
                                        <div class="input-group">
                                           <label class="switch switch-primary" title="Status">
                                                <input type="checkbox"  class="changeStatus"  data-rel="' . $val->id . '" ' . $checked . '>                            
                                                <span class="handle"></span>
		           </label>
                                        </div>
                                    </td>    
                                     ';
                $html .= '<td>
			<a href="/admin/comlex_level_first_d2_qbankcategory?id=' . $val->id . '"><i class="fa fa-edit"></i></a>
			&nbsp; &nbsp; &nbsp;<a href="javascript:void(0);" data-rel="' . $val->id . '" class="deleteRecord"><i class="fa fa-trash-o fa-lg"></i></a>
		</td>';
                $html .= '</tr>';
                $html .= Self::get_qbank_category_with_child_tr($val->id, $pass + 1, 0);

            endforeach;
        }
        return $html;
    }
    
    
    public static function getDiscipline(){
        $discipline = Self::join('comlex_level_1_category as self_cl1','comlex_level_1_category.id','=','self_cl1.parent_id')
                      ->where('comlex_level_1_category.parent_id',0)
                      ->get(['self_cl1.id','self_cl1.title']);
        return $discipline;
    }
    
    public static function getAllDiscipline(){
        $discipline = Self::join('comlex_level_1_category as self_cl1','comlex_level_1_category.id','=','self_cl1.parent_id')
                      ->where('comlex_level_1_category.parent_id',0)
                      ->where('self_cl1.status','Enabled')
                      ->get(['self_cl1.id','self_cl1.title'])
                      ->toArray();
        return $discipline;
    }
    
    public function childrens(){
        return $this->hasMany(Self::class,'parent_id');
    }
    
    public function allChildrens(){
        return $this->childrens()->with('allChildrens')->where('status','Enabled');
    }
    public static function getQbankCategories(){
        
        $categories = Self::join('comlex_level_1_questions','comlex_level_1_category.id','=','comlex_level_1_questions.category_id')
                                                    ->where('comlex_level_1_category.status','Enabled')
                                                    ->where('comlex_level_1_questions.status','Enabled')
                                                    ->groupBy('comlex_level_1_questions.category_id')
                                                    ->select(
                                                          'comlex_level_1_category.id as category_id',
                                                          'comlex_level_1_category.parent_id as category_parent_id',
                                                          'comlex_level_1_category.title as title',
                                                          DB::raw('count(comlex_level_1_questions.id) as question_count')
                                                       )
                                                    ->get()->toArray();   
        
         return $categories;
    }
    
    
    
}
