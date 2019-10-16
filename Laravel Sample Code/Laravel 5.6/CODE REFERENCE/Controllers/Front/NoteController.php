<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteStoreRequest;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Store a newly created Comment in storage.
     *
     * @param Request $request
     * @return Note
     */
    public function store(Request $request)
    {
        $note = new Note();
        $note->fill($request->all());
        $note->user_id = \Auth::user()->id;
        $note->save();

        return $note;
    }

    /**
     * Ajax Store Note
     *
     * @param NoteStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAjax(NoteStoreRequest $request)
    {
        $this->store($request);
        return response()->json(['status' => 'success', 'message' => 'Note successful']);
    }
}
