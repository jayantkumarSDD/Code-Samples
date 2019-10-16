<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
Use App;
Use Validator;
Use Redirect;
Use Hash;
Use App\Models\FlashCard;
use App\Models\FlashCardCategory;
use App\Models\StudentAssessments;
Use Illuminate\Http\Request;

class FlashCardController extends Controller
{
    
    public function addUpdateFlashCard(Request $request) { 
        $input = $request->all();
        $validator = Validator::make($input, [
                        'term' => 'required',
                        'definition' => 'required'
        ]);
        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        } else {
            $status = FlashCard::addUpdateFlashCard($input);
            if ($status) {
                if (!empty($input['id'])) {
                    return Redirect::back()->with('message', 'Flashcard updated sucessfully!');
                } else {
                    return Redirect::back()->with('message', 'Flashcard added sucessfully!');
                }
            }
        }
         
    }
    /* Start the upload of the CSV file from here */ 
    /** 
        * function uploadCsvFlashcard to upload the flashcard
        * Request params : CSV file ( only csv )
        * Response: Upload the file
        * Created_at 16 Nov 2018
        * Created_by Vipin
    */
    public function uploadCsvFlashcard( Request $request )
    {
        $validator = Validator::make($request->all(), [
            'flashcardcsv' => 'required|mimes:csv,txt'
        ]);
        if ($validator->fails()) {
            return Redirect::back()->withErrors( $validator );
        }else{
            $category_id = 0;
            $customerArr = $this->csvToArray( $request['flashcardcsv'] );
            if ( count($customerArr ) > 0 ){
                $defaultCategoryName = "Osteopathic manipulative medicine";
                $checkExist = FlashCardCategory::checkAlreadyExists( $defaultCategoryName );
                if($checkExist->isEmpty()) {
                $var['name'] = $defaultCategoryName;
                $var['status'] = 'Enabled';
                $category_id = FlashCardCategory::addUpdateCategory( $var );
                }else{
                    $category_id = $checkExist[0]['id'];
                }
                if(!empty($category_id)){
                    $this->uploadCsvToDb($customerArr,$category_id);
                    return Redirect::back()->with('message', 'Flashcard uploaded sucessfully!');     
                }else{
                    return Redirect::back()->with('error_message', 'Please check if flash card category table exist');     
                }
            }else{
                return Redirect::back()->with('error_message', 'No record found in csv file!'); 
            }
        }

    }
    /** 
        * function csvToArray: to convert the CSV record into the Array
        * Request params : CSV file name
        * Response: Return the array of Terms and Condition list
        * Created_at 16 Nov 2018
        * Created_by Vipin
    */
    function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;
    
        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
            {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }
    
        return $data;
    }
    /** 
        * function uploadCsvToDb: This will save the Flashcard into the database
        * Request params : Terms and Condition array
        * Response: 
        * Created_at 16 Nov 2018
        * Created_by Vipin
    */
    public function uploadCsvToDb($records,$category_id)
    {
        foreach($records as $key=>$val){
            $vars = [];
            $checkExist = FlashCard::checkAlreadyExistTermDefinition($val);
            if(!empty($checkExist)){ //dd($checkExist);
                    $return_cat_id = $checkExist->flash_card_category_id;
                    if($return_cat_id != $category_id){
                        //Exist but update with category id
                        $vars['id'] =  $checkExist->id;
                        $vars['category'] = $category_id;
                        $vars['term'] = $checkExist->term;
                        $vars['definition'] = $checkExist->definition;
                        $vars['status'] = $checkExist->status; 
                        FlashCard::addUpdateFlashCard($vars); 
                    }
            }else{
                // Not exist case
                if(!empty($val['term']) && !empty($val['definition'])){
                    $vars['category'] = $category_id;
                    $vars['term'] = $val['term'];
                    $vars['definition'] = $val['definition'];
                    $vars['status'] = 'Enabled';
                    FlashCard::addUpdateFlashCard($vars);
                }
            }
        } return true;
    }
    /* End of The Uplaod CSV file here */ 
    
    public function showFlashCardPage() {
        if (isset($_GET['id']) && !empty($_GET['id'])) 
        {
            $flashcard = FlashCard::find($_GET['id']);
             $flashCardCategory = FlashCardCategory::get();

            return view('admin.flash_card.flashcard',compact('flashCardCategory'))->with('page_title', 'Edit FlashCard')->with('flashcard', $flashcard);
        }
        else
        {
            $flashCardCategory = FlashCardCategory::all();
           
            return view('admin.flash_card.flashcard', compact('flashCardCategory'))->with('page_title', 'Add FlashCard');
        }
    }
    
    
    public function showFlashCardList(Request $request) {
        $searchTerm = '';
        if($request->has('search'))
        {
            $searchTerm = $request->input('search');
            $flashcardlist = FlashCard::orderBy('id', 'desc')
                ->where(function($query) use($searchTerm) {
                    $query->orWhere('id', 'LIKE', "%$searchTerm%")
                        ->orWhere('term', 'LIKE', "%$searchTerm%")
                        ->orWhere('definition', 'LIKE', "%$searchTerm%")
                        ->orWhere('created_at', 'LIKE', "%$searchTerm%")
                        ->orWhere('updated_at', 'LIKE', "%$searchTerm%");
                })
                ->paginate(10);
             
        } 

        else {
            $flashcardlist = FlashCard::orderBy('id','ASC')->paginate(10);
            
        }
        return view('admin.flash_card.flashcardlist',compact('flashcardlist','searchTerm'))->with('page_title','Flashcard List');
    }
    





public function showStudentAssessmentList(Request $request) {


  return view('admin.flash_card.studentassessment')->with('page_title','Student Assessments');
    
        
    }
    
}
