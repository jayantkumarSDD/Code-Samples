<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
Use App;
Use Validator;
Use Redirect;
Use Request;
Use Hash;
Use App\Models\VideoCategory;
Use App\Models\Videos;
Use App\Models\VideoMeta;
Use Vimeo;

class VideoController extends Controller {
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

    public function add_update_video_category() {
        $input = Request::all();
        $input = array_map('trim', $input);
        $has_id = 0;
        if (isset($input['id'])) {
            $has_id = 1;
            $validator = Validator::make($input, [
                        'category_title' => ['required', 'unique:video_category,title,' . $input["id"]],
                        'category_status' => 'required',
            ]);
        } else {
            $validator = Validator::make($input, [
                        'category_title' => ['required', 'unique:video_category,title'],
                        'category_status' => 'required',
            ]);
        }
        if ($validator->fails()) {
            if ($has_id == 1) {
                return Redirect::to('/admin/category?id=' . $input['id'])->withErrors($validator);
            } else {
                return Redirect::to('/admin/category')->withErrors($validator);
            }
        } else {
            $status = VideoCategory::addUpdateVideoCategory($input);
            if ($status == true) {
                if ($has_id == 1) {
                    return Redirect::to('/admin/category?id=' . $input['id'])->with('message', 'Category updated sucessfully!');
                } else {
                    return Redirect::to('/admin/category')->with('message', 'Category added sucessfully!');
                }
            } else {
                if ($has_id == 1) {
                    return Redirect::to('/admin/category?id=' . $input['id'])->with('error_message', 'Something went wrong');
                } else {
                    return Redirect::to('/admin/category')->with('error_message', 'Something went wrong');
                }
            }
        }
    }

    public function add_update_video() {
        $input = Request::all();
        $input = array_map('trim', $input);
        $has_id = 0;
        if (isset($input['id'])) {
            $has_id = 1;
            $validator = Validator::make($input, [
                        'video_title' => ['required', 'unique:videos,title,' . $input["id"]],
                        'video_id' => 'required',
                        'video_category' => 'required',
                        'video_status' => 'required',
            ]);
        } else {
            $validator = Validator::make($input, [
                        'video_title' => ['required', 'unique:videos,title'],
                        'video_id' => 'required',
                        'video_category' => 'required',
                        'video_status' => 'required',
            ]);
        }
        if ($validator->fails()) {
            if ($has_id == 1) {
                return Redirect::to('/admin/video?id=' . $input['id'])->withErrors($validator);
            } else {
                return Redirect::to('/admin/video')->withErrors($validator);
            }
        } else {
            $status = Videos::addUpdateVideo($input);
            if ($status == true) {
                if ($has_id == 1) {
                    return Redirect::to('/admin/video?id=' . $input['id'])->with('message', 'Video updated sucessfully!');
                } else {
                    return Redirect::to('/admin/video')->with('message', 'Video added sucessfully!');
                }
            } else {
                if ($has_id == 1) {
                    return Redirect::to('/admin/category?id=' . $input['id'])->with('error_message', 'Something went wrong');
                } else {
                    return Redirect::to('/admin/category')->with('error_message', 'Something went wrong');
                }
            }
        }
    }

    public function showCategoryList() {
        $input = Request::all();
        $input = array_map('trim', $input);
        if (empty($input['search'])) {
            $categories = VideoCategory::getAll(10);
        } else {
            $result = VideoCategory::doSearch($input['search']);
            if ($result->isEmpty()) {
                $categories = VideoCategory::getAll(10);
            } else {
                $categories = $result;
            }
        }

        return view('admin.categorylist')->with('page_title', ' Category List')->with('categories', $categories);
    }

    public function showVideoList() {
        $input = Request::all();
        $input = array_map('trim', $input);
        if (empty($input['search'])) {
            $videos = VideoCategory::getAllVideoWithCategory(10);
        } else {
            $result = VideoCategory::doSearchVideo($input['search']);
            if ($result->isEmpty()) {
                $videos = VideoCategory::getAllVideoWithCategory(10);
            } else {
                $videos = $result;
            }
        }


        return view('admin.videolist')->with('page_title', ' Video List')->with('videos', $videos);
    }

    public function showCategoryPage() {
        $parents = VideoCategory::getAllParents();
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $category = VideoCategory::find($_GET['id']);
            return view('admin.category')->with('page_title', ' Add Category')->with('category', $category)->with('parents', $parents);
        } else {
            return view('admin.category')->with('page_title', ' Add Category')->with('parents', $parents);
        }
    }

    public function showVideoPage() {
        $categories = VideoCategory::getAllParents();
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $video = Videos::find($_GET['id']);
            return view('admin.video')->with('page_title', ' Add Video')->with('video', $video)->with('video_categories', $categories);
        } else {
            return view('admin.video')->with('page_title', ' Add Video')->with('video_categories', $categories);
        }
    }

    public function sync_video_from_vimeo() {
        if (isset($_GET['vimeo_id'])) {
            $videos = Videos::where('video_id', $_GET['vimeo_id'])->get();
        } else {
            $videos = Videos::all();
        }
        if (!$videos->isEmpty()):
            foreach ($videos as $video):
                if (!empty($video->video_id)):
                    $data = Vimeo::request('/videos/' . $video->video_id, array(), 'GET');
                    $video_meta = VideoMeta::firstOrNew(['video_id' => $video->video_id]);
                    $video_meta->video_meta = serialize($data['body']);
                    $video_meta->created_date = $data['body']['created_time'];
                    $video_meta->modified_date = $data['body']['modified_time'];
                    $video_meta->save();
                endif;
            endforeach;
            return Redirect::to('/admin/videolist')->with('message', 'Synchronization has been done');
        endif;
    }

}
