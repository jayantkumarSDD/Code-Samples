<?php

namespace App\Http\Controllers\Front;

use App\Http\Requests\ErrorReportStoreRequest;
use App\Models\ErrorReport;
use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ErrorReportController extends Controller
{
    /**
     * Store a newly created Comment in storage.
     *
     * @param Request $request
     * @return ErrorReport
     */
    public function store(Request $request)
    {
        $errorReport = new ErrorReport();
        $errorReport->fill($request->all());
        $errorReport->user_id = \Auth::user()->id;
        $errorReport->save();

        return $errorReport;
    }

    /**
     * Ajax Store Comment
     *
     * @param ErrorReportStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAjax(ErrorReportStoreRequest $request)
    {
        $this->store($request);
        return response()->json(['status' => 'success', 'message' => 'Comment successful']);
    }
}
