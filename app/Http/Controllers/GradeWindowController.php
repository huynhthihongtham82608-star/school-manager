<?php

namespace App\Http\Controllers;

use App\Models\GradeWindow;
use Illuminate\Http\Request;

class GradeWindowController extends Controller
{
    public function index()
    {
        return redirect()
            ->route('exam-schedules.index')
            ->with('success', 'Cửa sổ nhập điểm hiện được quản lý trong module Lịch kiểm tra.');
    }

    public function store(Request $request)
    {
        return redirect()
            ->route('exam-schedules.index')
            ->with('success', 'Vui lòng mở hoặc khóa nhập điểm tại từng kỳ kiểm tra trong module Lịch kiểm tra.');
    }

    public function update(Request $request, GradeWindow $gradeWindow)
    {
        return redirect()
            ->route('exam-schedules.index')
            ->with('success', 'Vui lòng mở hoặc khóa nhập điểm tại từng kỳ kiểm tra trong module Lịch kiểm tra.');
    }
}
