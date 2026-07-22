<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $destinations = [
            ['route' => 'school-years.index', 'permission' => 'academic.manage'],
            ['route' => 'semesters.index', 'permission' => 'academic.manage'],
            ['route' => 'subjects.index', 'permission' => 'subjects.manage'],
            ['route' => 'departments.index', 'permission' => 'departments.manage'],
            ['route' => 'rooms.index', 'permission' => 'rooms.manage'],
            ['route' => 'classes.index', 'permission' => 'classes.manage'],
            ['route' => 'assignments.index', 'permission' => 'assignments.manage'],
            ['route' => 'timetable.manage', 'permission' => 'timetable.manage'],
            ['route' => 'exam-schedules.index', 'permission' => 'exams.manage'],
            ['route' => 'attendance.index', 'permission' => 'attendance.view'],
            ['route' => 'scores.index', 'permission' => 'scores.view'],
            ['route' => 'conduct.index', 'permission' => 'conduct.view'],
        ];

        foreach ($destinations as $destination) {
            if ($user->hasPermission($destination['permission'])) {
                return redirect()->route($destination['route']);
            }
        }

        abort(403);
    }
}
