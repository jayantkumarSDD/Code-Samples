<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
Use App;
Use Validator;
Use Redirect;
Use Illuminate\Http\Request;
Use App\Models\CouponCode;
Use App\Models\ComlexLevelFirstQbankCategory;
Use App\Models\ComlexLevelSecondQbankCategory;
Use App\Models\ComlexLevelThirdQbankCategory;

class CouponCodeController extends Controller {

    public function showCouponPage(Request $request) {
        $discount_codes = [];
        $qbankCategoryLevel1 = ComlexLevelFirstQbankCategory::all();
        $qbankCategoryLevel2 = ComlexLevelSecondQbankCategory::all();
        $qbankCategoryLevel3 = ComlexLevelThirdQbankCategory::all();
        if ($request->has('id')) {
            $discount_codes = CouponCode::find($request->input('id'));
            return View('admin.coupon.coupon', compact('qbankCategoryLevel1', 'qbankCategoryLevel2', 'qbankCategoryLevel3'))->with('page_title', 'Edit Coupon')->with('discount_codes', $discount_codes);
        }
        return View('admin.coupon.coupon', compact('qbankCategoryLevel1', 'qbankCategoryLevel2', 'qbankCategoryLevel3'))->with('page_title', 'Add Coupon');
    }

    public function showUploadCouponCode() {
        return View('admin.coupon.upload_coupon')->with('page_title', 'Upload Coupon File');
    }

    public function addEditCoupon(Request $request) {
        $input = $request->all();
        if (isset($input['id']) && !empty($input['id'])) {
            $validator = Validator::make($input, [
                        'title' => ['required', 'unique:coupon_code,title,' . $input["id"]],
                        'code' => ['required', 'unique:coupon_code,code,' . $input["id"]],
                        'start_date' => ['required'],
                        'end_date' => ['required'],
                        'status' => 'required',
            ]);
        } else {
            $validator = Validator::make($input, [
                        'title' => ['required', 'unique:coupon_code,title'],
                        'code' => ['required', 'unique:coupon_code,code'],
                        'start_date' => ['required'],
                        'end_date' => ['required'],
                        'status' => 'required',
            ]);
        }
        $validator->sometimes('percentage', 'required|numeric|digits_between:1,100', function($input) {
            return $input['type'] == 'percentage';
        });

        $validator->sometimes(['off_amount', 'from_amount', 'to_amount'], 'required|numeric', function($input) {
            return $input['type'] == 'fixed';
        });

        $validator->sometimes('qbankCategories.*', 'required', function($input) {
            return $input['type'] == 'qbankCategory';
        });


        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        } else {
            $status = CouponCode::addUpdateCouponCode($input);
            if ($status == true) {
                return Redirect::back()->with('message', 'Code has been saved successfully!');
            } else {
                return Redirect::back()->with('error_message', 'Something went wrong');
            }
        }
    }

    public function showCouponCodeList(Request $request) {
        if ($request->has('search')) {
            $keyword = $request->input('search');
            $coupons = CouponCode::orWhere('id', 'LIKE', "%$keyword%")
                    ->orWhere('title', 'LIKE', "%$keyword%")
                    ->orWhere('code', 'LIKE', "%$keyword%")
                    ->orWhere('percentage', 'LIKE', "%$keyword%")
                    ->orWhere('start_date', 'LIKE', "%$keyword%")
                    ->orWhere('end_date', 'LIKE', "%$keyword%")
                    ->orWhere('created_at', 'LIKE', "%$keyword%")
                    ->orWhere('updated_at', 'LIKE', "%$keyword%")
                    ->orderBy('id', 'DESC')
                    ->paginate(10);
        } else {
            $coupons = CouponCode::orderBy('id', 'DESC')->paginate(10);
        }
        return view('admin.coupon.couponlist', compact('coupons'))->with('page_title', ' Coupon List');
    }

    public function uploadCouponCode(Request $request) {

        $input = $request->all();

        $validator = Validator::make($input, [
                    'upload_coupon_csv_file' => ['required', 'file', 'max:1024', 'mimes:csv,xls,xlsx,ods,ots,xlw,xlt'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator);
        } else {
            $path = $request->file('upload_coupon_csv_file')->getRealPath();
            $data = \Excel::load($path, function($reader) { })->get();
            if (!empty($data) && $data->count()) {
                foreach ($data->toArray() as $key => $value) {
                    if (!empty($value)) {
                        foreach ($value as $v) {
                            $insert[] = ['title' => $v, 'code' => $v, 'type' => 'qbankCategory', 'qbankCategories' => '9,20'];
                        }
                    }
                }
                if (!empty($insert)) {
                    CouponCode::insert($insert);
                    return back()->with('message', 'Insert Record successfully.');
                } else {
                    return back()->with('error_message', 'File is empty');
                }
            }
        }
    }

}
