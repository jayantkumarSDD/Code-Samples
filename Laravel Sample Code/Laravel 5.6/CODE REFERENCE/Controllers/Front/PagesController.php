<?php

namespace App\Http\Controllers\Front;

use App\Models\User;
use App\Http\Controllers\Controller;
use Redirect;
use DB;
use App\Models\Plans;
use App\Models\Page;
use Illuminate\Http\Request;
use Validator;
Use Mail;
Use Session;

class PagesController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        
    }

    public function showHomePage() {
        if (\Auth::user()) {
            return Redirect::to('/student/dashboard');
        } else {
            return view('frontend.home');
        }
    }

    public function showContactUsPage() {
        return view('frontend.contactus');
    }

    public function showInstitutionsPage() {
        return view('frontend.institutions');
    }

    public function showCompanyPage() {
        return view('frontend.company');
    }

    public function showFaqsPage() {
        return view('frontend.faq');
    }

    public function showComlexLevelPage($level) {
        $plans = Plans::where('status', 'Enabled')
                ->whereIn('category', [$level, $level . '-bundle'])
                ->get();
        $page = Page::whereIn('type', ['comlex-' . $level, $level])
                ->where('status', 'enabled')
                ->first();
        return View('frontend.comlex-level', compact('plans', 'page', 'level'));
    }

    public function showFlashcardPage() {
        return view('frontend.flashcards');
    }

    public function showVideosPage() {
        $videos = VideoCategory::getCategoriesWithVideos();
        return view('frontend.videos')->with('videos', $videos);
    }

    /**
     * Display specific Video details page
     * 
     * @param null $title
     * @param $video_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showVideoDetailsPage($title = null, $video_id) {
        $video = Videos::findEnabled($video_id);
        $page_title = 'Video';
        return view('frontend.videos-detail', compact('video', 'page_title'));
    }

    public function get_states_by_country_id($country_id) {
        if (!empty($country_id)):
            $states = DB::table('states')->where('country_id', $country_id)->get();
            echo json_encode($states);
        endif;
    }

    public function get_cities_by_state_id($state_id) {
        if (!empty($state_id)):
            $citis = DB::table('cities')->where('state_id', $state_id)->get();
            echo json_encode($citis);
        endif;
    }

    public function submitEnquiryComment(Request $request) {
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
                    'name' => 'required',
                    'email' => ['required', 'email'],
                    'comment' => ['required'],
                    'g-recaptcha-response' => 'required|captcha'
        ]);
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        } else {
            DB::table('contact_us')->insert(array(
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => isset($input['phone']) ? $input['phone'] : '',
                'comment' => $input['comment'],
            ));
            try {
                $status = Mail::send('emails.contactus', $input, function($message) {
                            $message->to('omtreview@hotmail.com', 'omtreview')->subject('Enquiry or Comment Form');
                            $message->bcc('ankitgpt222@gmail.com', 'omtreview')->subject('Enquiry or Comment Form');
                        });
                return Redirect::back()->with('message', 'Our support team will contact to you soon');
            } catch (Exception $e) {
                
            }
        }
    }

    public function showTermsConditionPage() {
        $page = Page::where('type', 'terms_condition')
                ->where('status', 'enabled')
                ->first();
        return view('frontend.company.terms_condition', compact('page'));
    }

    public function showPrivacyPolicyPage() {
        $page = Page::where('type', 'privacy_policy')
                ->where('status', 'enabled')
                ->first();
        return view('frontend.company.privacy_policy', compact('page'));
    }

    public function showNeedHelpPage() {
        return view('user.needhelp');
    }

    public function showProductPage() {
        $page = Page::where('type', 'products')
                ->where('status', 'enabled')
                ->first();
        return view('frontend.products', compact('page'));
    }

    public function showInstitutionsConsultationPage() {
        return view('user.institutional');
    }

    public function submitScheduleConsultationForm(Request $request) {
        $input = $request->all();
        $input = array_map('trim', $input);
        $validator = Validator::make($input, [
                    'name' => 'required',
                    'email' => ['required', 'email'],
                    'title' => 'required',
                    'school' => 'required',
                    'comment' => ['required'],
                    'g-recaptcha-response' => 'required|captcha'
        ]);
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        } else {
            try {
                $status = Mail::send('emails.schedule_consultation', $input, function($message) {
                            $message->to('admin@omtreview.com', 'omtreview')->subject('Institutional Consultation Enquiry');
                            $message->bcc('ankitgpt222@gmail.com', 'omtreview')->subject('Institutional Consultation Enquiry');
                        });
                return Redirect::back()->with('message', 'Our Institutional Consultation team will contact to you soon');
            } catch (Exception $e) {
                
            }
        }
    }
    
    public function showMembershipNotification(){
        if(Session::get('error_type'))
        {
            return view('errors.membership-notification');
        } else {
            return redirect()->to('/');
        }    
    }

}