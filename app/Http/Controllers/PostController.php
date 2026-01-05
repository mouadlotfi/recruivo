<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of published posts.
     */
    public function index()
    {
        $posts = Post::published()
            ->latest()
            ->paginate(12);

        return view('posts.index', compact('posts'));
    }

    /**
     * Display the specified post by its localized slug.
     */
    public function show(string $slug)
    {
        $locale = app()->getLocale();
        
        // Find post by localized slug using JSON query for better performance
        // This queries the database directly instead of loading all posts into memory
        $post = Post::published()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.{$locale}')) = ?", [$slug])
            ->first();

        if (!$post) {
            abort(404);
        }

        return view('posts.show', compact('post'));
    }
}

