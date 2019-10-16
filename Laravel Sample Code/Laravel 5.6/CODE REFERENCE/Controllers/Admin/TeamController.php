<?php

namespace App\Http\Controllers\Admin;
Use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
Use App\Models\Team;
use Validator;
Use Redirect;

class TeamController extends Controller
{

    public function __construct() {
        
    }

    /* @Name teamForm
     * @Param request
     * @Return html view and it's data
     * @Desc load add,update form
     *
     * */
    public function teamForm(Request $request) {
        $id = $request->input('id');

        if (!empty($id)) {
            $team = Team::find($id);
            return view('admin.our_team.team')->with(['page_title'=>'Update Team','team'=>$team]);
        }
        return view('admin.our_team.team')->with('page_title', 'Add Team');
    }

    /* @Name addTeam
     * @Param $request
     * @Return html view and it's data
     * @Desc insert form data of Team
     *
     * */

    public function addTeam(Request $request)
    {

        $data = $request->all();

        $validator =Validator::make($data,
            ['name'=>'required|max:255',
                'description'=>'required',
                'member_feature_image'=>['required','mimes:jpeg,bmp,png,gif,jpg']
            ]);

        if ($validator->fails()) {
            return Redirect::back()
                ->withErrors($validator)->with('team',$data);
        }

        if($request->hasFile('member_feature_image')):
            $destination = 'assets/frontend/images/upload/team';
            $file = $request->file('member_feature_image');
            $data['image'] =uploadFile($file,$destination);
        endif;

        $team = new Team();
        $team->fill($data);
        $saved =$team->save();
        if($saved):
           return Redirect('admin/team_list')->with('message','Member Added Successfully!');
        else:
            Redirect::back()->with('message','Something went wrong');
        endif;
    }

    /* @Name updateTeam
     * @Param $request
     * @Return  void
     * @Desc update Team data
     *
     * */

    public function updateTeam(Request $request)
    {

        $data = $request->all();

        if ($request->hasFile('member_feature_image')) {
            $validator =Validator::make($data,
                ['name'=>'required|max:255',
                    'description'=>'required',
                    'member_feature_image'=>['required','mimes:jpeg,bmp,png,gif,jpg']
                ]);
        }
        else {
            $validator =Validator::make($data,
                ['name'=>'required|max:255',
                    'description'=>'required'
                ]);
        }


        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        }

        if($request->hasFile('member_feature_image')):
            $destination = 'assets/frontend/images/upload/team';
            $file = $request->file('member_feature_image');
            $data['image'] =uploadFile($file,$destination);
        endif;

        $team = Team::find($data['id']);
        $team->name =$data['name'];
        $team->description =$data['description'];
        $team->status =$data['status'];
        if(!empty($data['image'])){
         $team->image =$data['image'];
        }

        $updated =$team->save();
        if($updated){
            return Redirect::back()->with('message','Member Updated Successfully!');
        }else{
            return Redirect::back()->with('message','Something Went Wrong');
        }

    }

    /* @Name showTeamList
     * @Param $data
     * @Return Team data
     * @Desc get Team data{ search or directly} from database
     *
     * */

    public function showTeamList(Request $request)
    {
        if(isset($request['search']) && !empty($request['search']))
        {
            $keyword = $request['search'];
            $team = Team::orderBy('id','Desc')
                ->where(function ($query) use($keyword){
                    $query->orWhere('id','LIKE',"%$keyword%")
                        ->orWhere('name','LIKE',"%$keyword%")
                        ->orWhere('description','LIKE',"%$keyword%")
                        ->orWhere('created_at','LIKE',"%$keyword%")
                        ->orWhere('updated_at','LIKE',"%$keyword%");


                }) ->paginate(10);
        }
        else
        {
            $team = Team::orderBy('id','DESC')->paginate(10);
        }

        return view('admin.our_team.team_list')->with('page_title', 'Team(s) List')->with('teams',$team);

    }
    
    public function deleteTeamImage($id = NULL)
    {
        $team = Teams::find($id);
        unlink(public_path().$team->image);
        $team->image = '';
        $team->save();
        echo true;
    }
}
