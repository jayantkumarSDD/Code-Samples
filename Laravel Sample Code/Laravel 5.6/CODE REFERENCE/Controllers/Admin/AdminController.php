<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
Use App\Http\Controllers\Controller;
Use App;
Use DB;
Use Validator;
Use Redirect;
Use App\Models\User;

class AdminController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Welcome Controller
      |--------------------------------------------------------------------------
      |
      | This controller renders the "marketing page" for the application and
      | is configured to only allow guests. Like most of the other sample
      | controllers, you are free to modify or remove it as you desire.
      |
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        
    }

    public function showDashboard() {
        return view('admin.dashboard.dashboard')->with('page_title', 'Dashboard');
    }

    public function change_status(Request $request) {
        $input = $request->all();
        if (!empty($input['forAction']) && !empty($input['datarel']) && !empty($input['status'])) {
            $table_action = getDecrypt($input['forAction']);
            switch ($table_action):
                case 'videoCategory':
                    $table = 'video_category';
                    break;
                case 'video':
                    $table = 'videos';
                    break;
                case 'base_comlex_level_1':
                    $table = 'comlex_level_1_base_questions';
                    break;
                case 'base_comlex_level_2':
                    $table = 'comlex_level_2_base_questions';
                    break;
                case 'base_comlex_level_3':
                    $table = 'comlex_level_3_base_questions';
                    break;
                case 'category_comlex_level_1':
                    $table = 'comlex_level_1_category';
                    break;
                case 'category_comlex_level_2':
                    $table = 'comlex_level_2_category';
                    break;
                case 'category_comlex_level_3':
                    $table = 'comlex_level_3_category';
                    break;
                case 'comlex_level_first_d1_categories':
                    $table = 'comlex_level_first_d1_categories';
                    break;
                case 'comlex_level_first_d2_categories':
                    $table = 'comlex_level_first_d2_categories';
                    break;
                case 'questions_comlex_level_1':
                    $table = 'comlex_level_1_questions';
                    break;
                case 'questions_comlex_level_2':
                    $table = 'comlex_level_2_questions';
                    break;
                case 'questions_comlex_level_3':
                    $table = 'comlex_level_3_questions';
                    break;
                case 'flashCardCategory':
                    $table = 'flash_card_category';
                    break;
                case 'banners':
                    $table = 'banners';
                    break;
                case 'blogs':
                    $table = 'blogs';
                    break;
                case 'testimonials':
                    $table = 'testimonials';
                    break;
                case 'faqs':
                    $table = 'faqs';
                    break;
                case 'teams':
                    $table = 'teams';
                    break;
                case 'pages':
                    $table = 'pages';
                    break;
                case 'flashcard':
                    $table = 'flash_cards';
                    break;
                case 'subscriptions':
                    $table = 'subscription_plan';
                    break;
                case 'plans':
                    $table = 'plans';
                    break;
                case 'coupons':
                    $table = 'coupon_code';
                    break;
                case 'users':
                    $table = 'users';
                    break;
                
            endswitch;
            $id = $input['datarel'];
            $status = DB::table($table)->where('id', $id)->update(array('status' => $input['status']));
            echo $status;
        } else {
            die('Something went wrong');
        }
    }

    public function delete_record(Request $request) {
        $input = $request->all();
        if (!empty($input['forAction']) && !empty($input['datarel'])) {
            $table_action = getDecrypt($input['forAction']);
            switch ($table_action):
                case 'videoCategory':
                    $table = 'video_category';
                    break;
                case 'video':
                    $table = 'videos';
                    break;
                case 'base_comlex_level_1':
                    $table = 'comlex_level_1_base_questions';
                    break;
                case 'base_comlex_level_2':
                    $table = 'comlex_level_2_base_questions';
                    break;
                case 'base_comlex_level_3':
                    $table = 'comlex_level_3_base_questions';
                    break;
                case 'category_comlex_level_1':
                    $table = 'comlex_level_1_category';
                    break;
                 case 'category_comlex_level_2':
                    $table = 'comlex_level_2_category';
                    break;
                case 'category_comlex_level_3':
                    $table = 'comlex_level_3_category';
                    break;
                case 'comlex_level_first_d1_categories':
                    $table = 'comlex_level_first_d1_categories';
                    break;
                case 'comlex_level_first_d2_categories':
                    $table = 'comlex_level_first_d2_categories';
                    break;
                case 'questions_comlex_level_1':
                    $table = 'comlex_level_1_questions';
                    break;
                case 'questions_comlex_level_2':
                    $table = 'comlex_level_2_questions';
                    break;
                case 'questions_comlex_level_3':
                    $table = 'comlex_level_3_questions';
                    break;
                case 'flashCardCategory':
                    $table = 'flash_card_category';
                    break;
                case 'banners':
                    $table = 'banners';
                    break;
                case 'blogs':
                    $table = 'blogs';
                    break;
                case 'testimonials':
                    $table = 'testimonials';
                    break;
                case 'faqs':
                    $table = 'faqs';
                    break;
                case 'teams':
                    $table = 'teams';
                    break;
                case 'pages':
                    $table = 'pages';
                    break;
                case 'flashcard':
                    $table = 'flash_cards';
                    break;
                case 'subscriptions':
                    $table = 'subscription_plan';
                    break;
                case 'plans':
                    $table = 'plans';
                    break;
                case 'coupons':
                    $table = 'coupon_code';
                    break;
                case 'users':
                    $table = 'users';
                    break;
            endswitch;
            $id = $input['datarel'];
            if (!empty($table)) {
                /*$imageUrl = DB::table($table)->where('id', $id)->pluck('image');
                if(!empty($imageUrl)):
                    list($image) =$imageUrl;

                    unlink(str_replace('/assets','assets',$image));
                endif;
                 * 
                 */
                $status = DB::table($table)->where('id', $id)->delete();
                if (!empty($input['forForiegnAction'])) {
                    $foriegn_Column = $table . '_id';
                    $foriegn_table = getDecrypt($input['forForiegnAction']);
                    DB::table($foriegn_table)->where($foriegn_Column, $id)->delete();
                }
                echo $status;
            } else {
                die('Something went wrong');
            }
        }
    }

    public function uploadImage(Request $request) {

        if ($request->hasFile('file')) {
            $actual_name = $request->file('file')->getClientOriginalName();
            $name_of_file = preg_replace('!\s+!', '-', $actual_name);
            $fileName = gmdate('Ymdhis') . $name_of_file;
            $return_url = $request->file('file')->move('./assets/images/qbank/', $fileName);

            if ($return_url) {
                $url = '/assets/images/qbank/' . $fileName;
                list($width, $height) = getimagesize('./assets/images/qbank/' . $fileName);
                $return_array = array('url' => $url, 'height' => $height, 'width' => $width, 'file_name' => $fileName);
                echo json_encode($return_array);
            } else {
                echo false;
            }
        }
    }

    public function deleteImage(Request $request) {
        if ($request->has('datarel') && $request->has('forAction')) {
            $virtualTable = getDecrypt($request->forAction);
            $id = $request->input('datarel');
            if (!empty($virtualTable)):

                switch ($virtualTable):
                    case 'testimonials':
                        $table = 'testimonials';
                        break;
                    case 'blogs':
                        $table = 'blogs';
                        break;
                    case 'banners':
                        $table = 'banners';
                        break;
                    case 'faqs':
                        $table = 'faqs';
                        break;
                    case 'teams':
                        $table = 'teams';
                        break;
                        case 'pages':
                            $table = 'pages';
                            break;
                endswitch;

                $imageUrl = DB::table($table)->where('id', $id)->pluck('image');
                if (!empty($imageUrl)):

                    list($image) = $imageUrl;
                    $status = DB::table($table)->where('id', $id)->update(['image' => '']);
                    if ($status): unlink(public_path() . $image);
                    endif;

                    echo true;
                else:
                    echo false;
                endif;
            endif;
        }
    }

}
