<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
Use App\Models\Page;
use Validator;
Use Redirect;

class CmsPagesController extends Controller
{
    public function __construct()
    {
        
    }

    /* @Name cmsForm
     * @Param request
     * @Return html view and it's data
     * @Desc load add,update form
     *
     * */

    public function cmsForm(Request $request)
    {
        if($request->has('id')):

            $page = Page::find($request->id);
           return view('admin.cms_pages.cms_form')->with('pages',$page)->with('page_title','Update Page');
        else:

            return view('admin.cms_pages.cms_form')->with('page_title',' Add Page');
        endif;



    }

    /* @Name addCmsPages
     * @Param $request
     * @Return html view and it's data
     * @Desc insert form data of page
     *
     * */

    public function addCmsPages(Request $request){

        $inputData = $request->all();

        $validate = Validator::make($inputData,[
            'title'=>'required|max:255',
            'type'=>'required|max:255',
            'description'=>'required',
            'status'=>'required'
        ]);

        if($validate->fails()){

            return redirect('admin/cmsForm')->withErrors($validate)->with('cms',$inputData);
        }

        if ($request->hasFile('page_image')) :
            $destinationPath = 'assets/frontend/images/upload/cms';
            $file = $request->file('page_image');
            $inputData['image'] = uploadFile($file, $destinationPath);
        endif;

            $page = new Page();
            $page->fill($inputData);
            $commit = $page->save();

            if($commit):
              return redirect('admin/page_list')->with('message',' Page Added Successfully');
            else:
                return view('admin.cms_pages.cms_form')->with('message',' Something went wrong!');
            endif;

    }

    /* @Name updatePages
     * @Param $request
     * @Return  void
     * @Desc update Pages data
     *
     * */

    public function updatePages(Request $request)
    {
        $inputData = $request->all();

        $validate = Validator::make($inputData,[
            'title'=>'required|max:255',
            'type'=>'required|max:255',
            'description'=>'required',
            'status'=>'required'
        ]);

        if($validate->fails()){

            return redirect::back()->withErrors($validate);
        }

        if ($request->hasFile('page_image')) :
            $destinationPath = 'assets/frontend/images/upload/cms';
            $file = $request->file('page_image');
            $inputData['image'] = uploadFile($file, $destinationPath);
        endif;

        $page =Page::find($request->id);
        $page->title= $inputData['title'];
        $page->type= $inputData['type'];
        $page->description= $inputData['description'];
        $page->status= $inputData['status'];
        if(!empty($inputData['image']))
        $page->image= $inputData['image'];
        $commit = $page->save();
        if($commit):
            return redirect::back()->with('message',' Page Updated Successfully');
        else:
            return redirect::back()->with('message',' Something went wrong!');
        endif;
    }

    /* @Name showPages
     * @Param $data
     * @Return Team data
     * @Desc get pages data{ search or directly} from database
     *
     * */

    public function showPages(Request $request){


       if($request->has('search')):
           $keywords = trim($request->search);
           $page = Page::orderBy('id','DESC')
           ->where(function ($query) use($keywords){
                $query->orWhere('title','like',"%$keywords%")
                    ->orWhere('type','like',"%$keywords%")
                    ->orWhere('description','like',"%$keywords%")
                    ->orWhere('created_at', 'LIKE', "%$keywords%")
                    ->orWhere('updated_at', 'LIKE', "%$keywords%");

           })->paginate(10);
          else:
            $page = Page::orderBy('id','Desc')->paginate(10);
       endif;
        return view('admin.cms_pages.cms_page_list')->with(['page_title'=>'Page(s) List','pages'=>$page]);

    }
}
