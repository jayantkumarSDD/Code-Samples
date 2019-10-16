<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
Use App\Models\Faq;
use Validator;
Use Redirect;

class FaqController extends Controller {
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

    /* @Name faqForm
     * @Param request
     * @Return html view and it's data
     * @Desc load add,update form
     *
     * */

    public function faqForm(Request $request) {
        $id = $request->input('id');

        if (!empty($id)) {
            $faq = Faq::find($id);
            return view('admin.faq.faq')->with('page_title', 'Update FAQ(s)')->with('faq', $faq);
        }
        return view('admin.faq.faq')->with('page_title', 'Add FAQ(s)');
    }

    /* @Name addFaq
     * @Param $request
     * @Return html view and it's data
     * @Desc insert form data of FAQ
     *
     * */

    public function addFaq(Request $request) {

        $data = $request->all();

        $validator = Validator::make($data, ['title' => 'required|max:255',
                    'description' => 'required'
        ]);

        if ($validator->fails()) {
            return Redirect::back()
                            ->withErrors($validator)->with('faq', $data);
        }


        $faq = new Faq();
        $faq->fill($data);
        $saved = $faq->save();
        if ($saved):
            return redirect('admin/faq_list')->with('message', ' FAQ(s) Added Successfully!');
        else:
            Redirect::back()->with('message', 'Something went wrong');
        endif;
    }

    /* @Name updateFaq
     * @Param $request
     * @Return  void
     * @Desc update FAQ data
     *
     * */

    public function updateFaq(Request $request) {

        $data = $request->all();

        $validator = Validator::make($data, ['title' => 'required|max:255',
                    'description' => 'required'
        ]);

        if ($validator->fails()) {
            return Redirect::back()
                            ->withErrors($validator)->with('addFaq', $data);
        }

        $faq = faq::find($data['id']);
        $faq->title = $data['title'];
        $faq->description = $data['description'];
        $faq->status = $data['status'];
        $updated = $faq->save();
        if ($updated) {
            return Redirect::back()->with('message', 'FAQ(s) Updated Successfully!');
        } else {
            return Redirect::back()->with('message', 'Something Went Wrong');
        }
    }

    /* @Name showFaqList
     * @Param $data
     * @Return FAQ data
     * @Desc get FAQ data{ search or directly} from database
     *
     * */

    public function showFaqList(Request $request) {
        if (isset($request['search']) && !empty($request['search'])) {
            $keyword = $request['search'];
            $faqs = Faq::orderBy('id', 'Desc')
                            ->where(function ($query) use($keyword) {
                                $query->orWhere('id', 'LIKE', "%$keyword%")
                                ->orWhere('title', 'LIKE', "%$keyword%")
                                ->orWhere('description', 'LIKE', "%$keyword%")
                                ->orWhere('created_at', 'LIKE', "%$keyword%")
                                ->orWhere('updated_at', 'LIKE', "%$keyword%");
                            })->paginate(10);
        } else {
            $faqs = Faq::orderBy('id', 'DESC')->paginate(10);
        }

        return view('admin.faq.faqlist')->with('page_title', 'FAQ(s) List')->with('faqs', $faqs);
    }

}
