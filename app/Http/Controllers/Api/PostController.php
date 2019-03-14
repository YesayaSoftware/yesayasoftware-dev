<?php

namespace App\Http\Controllers\Api;

use App\Post;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    /**
     * Create a new PostController instance.
     */
    public function __construct()
    {
//        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::latest()->get();

        return response([
            "posts" => ["post_list" => $posts]
        ], Response::HTTP_ACCEPTED);
    }
}
