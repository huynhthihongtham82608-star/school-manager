<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemRegulationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cards = collect();

        if ($user->hasPermission('attendance.view')) {
            $cards->push([
                'icon' => 'bi-person-x',
                'title' => 'Quy định vắng tiết',
                'description' => 'Theo dõi chuẩn phân loại vắng có phép, vắng không phép, đi muộn và cảnh báo chuyên cần.',
                'href' => route('attendance.index'),
                'action' => 'Mở điểm danh',
            ]);
        }

        if ($user->hasPermission('scores.manage')) {
            $cards->push([
                'icon' => 'bi-sliders',
                'title' => 'Định mức cột điểm',
                'description' => 'Cấu hình đầu điểm, hệ số và số lượng cột điểm theo từng môn học, khối lớp.',
                'href' => route('score-columns.index'),
                'action' => 'Mở cấu hình điểm',
            ]);

            $cards->push([
                'icon' => 'bi-calendar-check',
                'title' => 'Thời hạn nhập điểm',
                'description' => 'Quản lý cửa sổ nhập và chỉnh sửa điểm theo năm học, học kỳ.',
                'href' => route('grade-windows.index'),
                'action' => 'Mở thời hạn',
            ]);
        }

        if ($user->hasAnyPermission(['conduct.view', 'conduct.manage'])) {
            $cards->push([
                'icon' => 'bi-star',
                'title' => 'Xếp loại hạnh kiểm',
                'description' => 'Đối soát gợi ý hạnh kiểm dựa trên chuyên cần và điểm trung bình, giáo viên chủ nhiệm vẫn chốt thủ công.',
                'href' => route('conduct.index'),
                'action' => 'Mở hạnh kiểm',
            ]);
        }

        return view('system.regulations', compact('cards'));
    }
}
