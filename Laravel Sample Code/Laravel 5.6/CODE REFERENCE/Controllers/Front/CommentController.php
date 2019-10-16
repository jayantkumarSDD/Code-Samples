<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentStoreRequest;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{

    /**
     * Store a newly created Comment in storage.
     * 
     * @param Request $request
     * @return Comment
     */
    public function store(CommentStoreRequest $request)
    {
        $comment = new Comment();
        $comment->fill($request->all());
        $comment->user_id = \Auth::user()->id;
        $comment->save();

        return $comment;
    }

    /**
     * Ajax Store Comment
     * 
     * @param CommentStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAjax(CommentStoreRequest $request)
    {
        $this->store($request);
        return response()->json(['status' => 'success', 'message' => 'Comment successful']);
    }
}
