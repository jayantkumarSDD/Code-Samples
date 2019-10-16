<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
Use App;
Use Validator;
Use Redirect;
Use Illuminate\Http\Request;
Use App\Models\Plans;



class PlanController extends Controller {
    
    public function showPlanPage(Request $request){
        $plan = [];
        if($request->has('id')){
            $plan = Plans::find($request->input('id'));
            return View('admin.plans.plan')->with('page_title', 'Edit Plan')->with('plan',$plan);        
        }
        return View('admin.plans.plan')->with('page_title', 'Add Plan');
    }
    
    public function addEditPlan(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
                    'category' => 'required|in:level-1,level-2,level-3,video,flashcard,level-1-bundle,level-2-bundle,level-3-bundle,book',
                    'title' => 'required',
                    'cart_title' => 'required',
                    'regular_price' => 'required|numeric',
                    'sales_price' => 'required|numeric',
                    'validity' => 'required|numeric',
                    'status' => 'required|in:Enabled,Disabled'
        ]);
        if ($validator->fails()) {
            return Redirect::back()
                            ->withErrors($validator)->with('plan', $input);
        } else {
            $status = Plans::addUpdatePlan($input);
            if ($status) {
                if (!empty($input['id'])) {
                    return Redirect::back()->with('message', 'Plan updated sucessfully!');
                } else {
                    return Redirect::back()->with('message', 'Plan added sucessfully!');
                }
            }
        }
    }

    public function showPlanList(Request $request){
       if($request->has('search'))
        {
            $keyword = $request->input('search');
            $plans  =  Plans::orderBy('id', 'desc')
                            ->where(function($query) use($keyword) {
                                $query->orWhere('id', 'LIKE', "%$keyword%")
                                    ->orWhere('category', 'LIKE', "%$keyword%")
                                    ->orWhere('title', 'LIKE', "%$keyword%")    
                                    ->orWhere('description', 'LIKE', "%$keyword%")
                                    ->orWhere('regular_price', 'LIKE', "%$keyword%")
                                    ->orWhere('sales_price', 'LIKE', "%$keyword%")
                                    ->orWhere('validity', 'LIKE', "%$keyword%")
                                    ->orWhere('status', 'LIKE', "%$keyword%")    
                                    ->orWhere('created_at', 'LIKE', "%$keyword%")
                                    ->orWhere('updated_at', 'LIKE', "%$keyword%");
                            })
                            ->paginate(10);
        } else {
            $plans = Plans::orderBy('id','DESC')->paginate(10);
        }
        return View('admin.plans.planlist')->with('page_title','Plan List')->with('plans',$plans);
    }
}
