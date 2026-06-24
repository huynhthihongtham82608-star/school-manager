<?php

namespace App\Http\Controllers;

use App\Models\SchoolPost;
use Illuminate\Support\Facades\Schema;

class AnnouncementController extends Controller
{
    public function index()
    {
        $posts = Schema::hasTable('school_posts')
            ? SchoolPost::where('is_published', true)->latest('published_at')->latest()->paginate(10)
            : collect();

        return view('announcements.index', compact('posts'));
    }
}
