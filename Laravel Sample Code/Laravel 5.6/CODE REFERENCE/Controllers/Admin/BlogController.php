<?php

namespace App\Http\Controllers\Admin;
Use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
Use App;
Use Input;
Use Validator;
Use Redirect;
Use App\Models\Blog;

class BlogController extends Controller {



    public function __construct()
    {
        
    }

    /* @Name blog
     * @Param request
     * @Return html view and it's data
     * @Desc load add,update form
     *
     * */

    public function blog(Request $request)
    {
        $id = $request->input('id');

        if(!empty($id)){
            $blog = Blog::find($id);
            return view('admin.blogs.blog')->with('page_title', 'Update Blog')->with('blog',$blog);
        }
        return view('admin.blogs.blog')->with('page_title', 'Add Blog');
    }

    /* @Name add_blogs
     * @Param $request
     * @Return html view and it's data
     * @Desc insert form data of blog
     *
     * */

    public function add_blogs(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
            'title' => 'required|max:255',
            'description' => 'required',
            'meta_title' => 'max:255',
            'meta_keywords' => 'max:255',
            'image' => ['mimes:jpeg,bmp,png,gif,jpg']
        ]);

        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator)->with('blog',$input);
        }

        if($request->hasFile('blog_feature_image')) :
            $destinationPath = 'assets/frontend/images/upload/blog';
            $file = $request->file('blog_feature_image');
            $input['image'] = uploadFile($file,$destinationPath);
        endif;

        $blog = new Blog();
        $blog->fill($input);
        $blog->save();
        return redirect::to('admin/blog_list')->with('message','Added');
    }


    public function showBlog(Request $request) {

        if($request->has('search'))
        {
            $keyword = $request->input('search');
            $blogs = Blog::orderBy('id', 'desc')
                ->where(function($query) use($keyword) {
                    $query->orWhere('id', 'LIKE', "%$keyword%")
                        ->orWhere('title', 'LIKE', "%$keyword%")
                        ->orWhere('description', 'LIKE', "%$keyword%")
                        ->orWhere('created_at', 'LIKE', "%$keyword%")
                        ->orWhere('updated_at', 'LIKE', "%$keyword%");
                })
                ->paginate(10);
        }else{
            $blogs = Blog::orderBy('id','DESC')->paginate(10);
        }


        return view('admin.blogs.bloglist')->with('page_title','Blog List')->with('blogs',$blogs);


    }



    public function updateBlog(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'title' => 'required|max:255',
            'description' => 'required',
            'meta_title' => 'max:255',
            'meta_keywords' => 'max:255',
            'id' =>'required',
            'blog_feature_image' =>['mimes:jpeg,bmp,png,gif,jpg']
        ]);


        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator);

        }

        if($request->hasFile('blog_feature_image')) :
            $destinationPath = 'assets/frontend/images/upload/blog';
            $file = $request->file('blog_feature_image');
            $input['image'] = uploadFile($file,$destinationPath);
        endif;


        $blog = Blog::find($input['id']);
        $blog->title = $input['title'];
        $blog->meta_title = $input['meta_title'];
        $blog->meta_keywords = $input['meta_title'];
        $blog->meta_description = $input['meta_description'];
        $blog->description = $input['description'];
        $blog->status = $input['status'];
        if(!empty($input['image'])) :
            $blog->image = $input['image'];
        endif;
        $blog->save();

        return Redirect::back()->with('message','Updated Successfully');

    }



    public function deleteBlogImage(Request $request)
    {
        if($request->has('datarel'))
        {
            $id = $request->input('datarel');
            $blog = Blog::find($id);
            unlink(public_path().$blog->image);
            $blog->image = '';
            $blog->save();
            echo true;
        }  else {
            echo false;
        }
    }
}