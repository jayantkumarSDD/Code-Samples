<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
Use App\Models\Banner;
use Validator;
Use Redirect;

class BannerController extends Controller {

    public function __construct() {
       
    }

    public function add(Request $request) {
        $id = $request->input('id');

        if (!empty($id)) {
            $banner = Banner::find($id);
            return view('admin.banner.banner')->with('page_title', 'Update Banner')->with('banner', $banner);
        }
        return view('.admin.banner.banner')->with('page_title', 'Add Banner');
    }

    public function add_banner(Request $request) {

        $input = $request->all();
        $validator = Validator::make($input, [
            'title' => 'required|max:100',
            'sub_title' => 'max:255',
            'banner_image' => ['required', 'mimes:jpeg,bmp,png,gif,jpg']
        ]);

        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator)->with('banner',$input);
        }

        if ($request->hasFile('banner_image')) :
            $destinationPath = 'assets/frontend/images/upload/banners';
            $file = $request->file('banner_image');
            $input['image'] = uploadFile($file, $destinationPath);
        endif;
        $banner = new Banner();
        $banner->fill($input);
        $banner->save();
        return redirect::to('/admin/banner_list')->with('message', 'Added');
    }

    public function updateBanner(Request $request) {

        $input = $request->all();

        if ($request->hasFile('banner_image')) {
            $validator = Validator::make($input, [
                'title' => 'required|max:100',
                'sub_title' => 'max:255',
                'id' => 'required',
                'status' => 'required',
                'banner_image' => ['required', 'mimes:jpeg,bmp,png,gif,jpg']
            ]);
        }
        else {
            $validator = Validator::make($input, [
                'title' => 'required|max:255',
                'sub_title' => 'max:255',
                'status' => 'required',
                'id' => 'required'
            ]);
        }

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        }

        if ($request->hasFile('banner_image')) :
            $destinationPath = 'assets/frontend/images/upload/banners';
            $file = $request->file('banner_image');
            $input['image'] = uploadFile($file, $destinationPath);
        endif;


        $banner = Banner::find($input['id']);
        $banner->title = $input['title'];
        $banner->sub_title = $input['sub_title'];
        $banner->embed_video_url = !empty($input['embed_video_url']) ? $input['embed_video_url'] : NULL;
        $banner->status = $input['status'];
        if (!empty($input['image'])) :
            $banner->image = $input['image'];
        endif;
        $banner->save();

        return Redirect::back()->with('message', 'Updated Successfully');
    }

    public function showBanners(Request $request) {

        if($request->has('search')){
            $keyword = $request->input('search');
            $banners = Banner::orderBy('id', 'desc')
                ->where(function($query) use($keyword)
                {
                    $query->orWhere('id', 'LIKE', "%$keyword%")
                        ->orWhere('title', 'LIKE', "%$keyword%")
                        ->orWhere('sub_title', 'LIKE', "%$keyword%")
                        ->orWhere('created_at', 'LIKE', "%$keyword%")
                        ->orWhere('updated_at', 'LIKE', "%$keyword%");
                })
                ->paginate(10);
        } else {
            $banners = Banner::paginate(10);
        }

        return view('admin.banner.bannerlist')->with('page_title', 'Banner List')->with('banners', $banners);
    }

}