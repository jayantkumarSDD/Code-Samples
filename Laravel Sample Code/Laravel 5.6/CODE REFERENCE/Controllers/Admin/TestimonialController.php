<?php

namespace App\Http\Controllers\Admin;
Use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
Use App;
Use Input;
Use Validator;
Use Redirect;
Use App\Models\Testimonial;

class TestimonialController extends Controller {


        /**
         * Create a new controller instance.
         *
         * @return void
                */

    /* @name __construct
     * @param NO
     * @return void
     * @desc Check admin login or not
     *
     * */

    public function __construct()
    {
        
    }

    /* @Name testimonial
     * @Param request
     * @Return html view and it's data
     * @Desc load add,update form
     *
     * */

    public function testimonial(Request $request)
    {
        $id = $request->input('id');

        if(!empty($id)){
            $testimonial = Testimonial::find($id);
            return view('admin.testimonial.testimonial')->with('page_title', 'Update Testimonial')->with('testimonial',$testimonial);
        }
        return view('admin.testimonial.testimonial')->with('page_title', 'Add Testimonial');
    }

    /* @Name addTestimonial
     * @Param $request
     * @Return html view and it's data
     * @Desc insert form data of Testimonial
     *
     * */

    public function addTestimonial(Request $request)
    {
        $data = $request->all();

        $validator =Validator::make($data,
        ['title'=>'required|max:255',
          'description'=>'required',
          'testimonial_feature_image' => ['mimes:jpeg,bmp,png,gif,jpg']
            ]);

        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator)->with('testimonial',$data);
        }

        if($request->hasFile('testimonial_feature_image')):
        $file = $request->file('testimonial_feature_image');
        $destination = 'assets/frontend/images/upload/testimonial';
        $data ['image'] = uploadFile($file,$destination);
        endif;

        $testimonial = new Testimonial();
        $testimonial->fill($data);
        $testimonial->save();

        return redirect('admin/testimonials_list')->with('message','Testimonial Added Successfully!');


    }

    /* @Name updateTestimonial
     * @Param $request
     * @Return  void
     * @Desc update Testimonial data
     *
     * */

    public function updateTestimonial(Request $request)
    {

        $data = $request->all();
        $validator =Validator::make($data,
            ['title'=>'required|max:255',
                'description'=>'required',
                'testimonial_feature_image' => ['mimes:jpeg,bmp,png,gif,jpg']
            ]);

        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator);
        }

        if($request->hasFile('testimonial_feature_image')):
            $file = $request->file('testimonial_feature_image');
            $destination = 'assets/frontend/images/upload/testimonial';
            $data ['image'] = uploadFile($file,$destination);
        endif;

        $testimonial = Testimonial::find($data['id']);
        $testimonial->title =$data['title'];
        $testimonial->description = $data['description'];
        $testimonial->status = $data['status'];
        if(!empty($data['image'])){
            $testimonial->image = $data['image'];
        }
        $testimonial->save();

        return Redirect::back()->with('message','Updated Successfully');
    }



    public function deleteTestimonialImage($id = NULL)
    {
        $testimonial = Testimonials::find($id);
        unlink(public_path().$testimonial->image);
        $testimonial->image = '';
        $testimonial->save();
        echo true;
    }

    /* @Name show_testimonial_list
     * @Param $data
     * @Return testimonial data
     * @Desc get Testimonial data{ search or directly} from database
     *
     * */

    public function show_testimonial_list(Request $request)
    {
        if($request->has('search'))
        {
            $keyword = $request->search;
            $testimonials = Testimonial::orderBy('id', 'desc')
                ->where(function ($query) use($keyword){
                                    $query->orWhere('id','LIKE',"%$keyword%")
                                    ->orWhere('title','LIKE',"%$keyword%")
                                    ->orWhere('description','LIKE',"%$keyword%")
                                    ->orWhere('created_at','LIKE',"%$keyword%")
                                    ->orWhere('updated_at','LIKE',"%$keyword%");

                })->paginate(10);
        }
        else
        {
            $testimonials = Testimonial::orderBy('id','DESC')->paginate(10);
        }

        return view('admin.testimonial.testimoniallist')
                   ->with('testimonials', $testimonials)
                   ->with('page_title', 'Testimonial List');
    }




}