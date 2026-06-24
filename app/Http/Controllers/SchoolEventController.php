<?php

namespace App\Http\Controllers;

use App\Models\SchoolEvent;
use Illuminate\Support\Facades\Schema;

class SchoolEventController extends Controller
{
    public function index()
    {
        $events = Schema::hasTable('school_events')
            ? SchoolEvent::where('is_published', true)->orderBy('starts_at')->paginate(10)
            : collect();

        return view('events.index', compact('events'));
    }
}
