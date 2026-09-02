<?php

namespace App\Services\Chatbot;

class ChatbotToolRegistry
{
    public function all(): array
    {
        return [
            'get_school_statistics' => [
                'description' => 'Lấy thống kê tổng quan toàn trường như số học sinh, giáo viên, phụ huynh, lớp, môn, phòng học và tài khoản bị khóa.',
                'parameters' => ['type' => 'object', 'properties' => ['focus' => ['type' => 'string']]],
                'handler' => 'schoolStatistics',
            ],
            'get_class_student_count' => [
                'description' => 'Đếm sĩ số một lớp, liệt kê sĩ số từng lớp hoặc tìm lớp đông/ít học sinh nhất.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'class_name' => ['type' => 'string', 'description' => 'Tên lớp, ví dụ 10A1 hoặc 11 a 1.'],
                        'mode' => ['type' => 'string', 'enum' => ['single', 'all', 'largest', 'smallest']],
                    ],
                ],
                'handler' => 'classStudentCount',
            ],
            'get_class_students' => [
                'description' => 'Lấy danh sách học sinh của một lớp trong phạm vi được phép; nếu giáo viên dạy nhiều lớp mà chưa nêu lớp thì hỏi lại.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'class_name' => ['type' => 'string', 'description' => 'Tên lớp cần xem danh sách học sinh.'],
                    ],
                ],
                'handler' => 'classStudents',
            ],
            'get_teacher_classes' => [
                'description' => 'Lấy danh sách lớp/môn giáo viên đang trực tiếp giảng dạy và lớp chủ nhiệm canonical nếu có.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teacher_name' => ['type' => 'string', 'description' => 'Tên giáo viên nếu admin hỏi về một giáo viên cụ thể.'],
                    ],
                ],
                'handler' => 'teacherClasses',
            ],
            'get_teacher_homeroom_class' => [
                'description' => 'Lấy riêng lớp chủ nhiệm canonical của giáo viên từ classes.homeroom_teacher_id, không suy diễn từ teaching_assignments.role.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teacher_name' => ['type' => 'string', 'description' => 'Tên giáo viên nếu admin hỏi về một giáo viên cụ thể.'],
                    ],
                ],
                'handler' => 'teacherHomeroomClass',
            ],
            'get_teacher_schedule' => [
                'description' => 'Lấy lịch dạy của giáo viên theo ngày hoặc thứ trong tuần.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => ['type' => 'string'],
                        'day_of_week' => ['type' => 'integer'],
                        'teacher_name' => ['type' => 'string'],
                    ],
                ],
                'handler' => 'teacherSchedule',
            ],
            'get_class_subject_teachers' => [
                'description' => 'Lấy giáo viên đang dạy một lớp, có thể lọc theo môn học và trả số giáo viên distinct.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'class_name' => ['type' => 'string', 'description' => 'Tên lớp, ví dụ 10A1 hoặc 11 a 1.'],
                        'subject_name' => ['type' => 'string', 'description' => 'Tên môn học nếu cần lọc, ví dụ Toán.'],
                    ],
                ],
                'handler' => 'classSubjectTeachers',
            ],
            'get_class_homeroom_teacher' => [
                'description' => 'Lấy giáo viên chủ nhiệm của một lớp từ dữ liệu lớp học canonical.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'class_name' => ['type' => 'string', 'description' => 'Tên lớp cần xem giáo viên chủ nhiệm.'],
                    ],
                ],
                'handler' => 'classHomeroomTeacher',
            ],
            'get_child_subject_teachers' => [
                'description' => 'Dành cho phụ huynh: từ học sinh/con đã xác định, lấy giáo viên đang dạy lớp của con.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'student_id' => ['type' => 'string'],
                        'student_name' => ['type' => 'string'],
                        'student_code' => ['type' => 'string'],
                        'subject_name' => ['type' => 'string'],
                    ],
                ],
                'handler' => 'childSubjectTeachers',
            ],
            'get_student_scores' => [
                'description' => 'Lấy điểm trung bình môn, điểm thành phần hoặc học lực của học sinh trong phạm vi được phép.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'student_id' => ['type' => 'string'],
                        'student_name' => ['type' => 'string'],
                        'student_code' => ['type' => 'string'],
                        'subject_name' => ['type' => 'string'],
                        'semester' => ['type' => 'string'],
                    ],
                ],
                'handler' => 'studentScores',
            ],
            'get_student_timetable' => [
                'description' => 'Lấy thời khóa biểu của học sinh theo ngày/thứ.',
                'parameters' => ['type' => 'object', 'properties' => ['student_id' => ['type' => 'string'], 'student_name' => ['type' => 'string'], 'date' => ['type' => 'string'], 'day_of_week' => ['type' => 'integer']]],
                'handler' => 'studentTimetable',
            ],
            'get_student_attendance' => [
                'description' => 'Lấy lịch sử chuyên cần, số buổi vắng, đi muộn hoặc trạng thái điểm danh theo ngày của học sinh.',
                'parameters' => ['type' => 'object', 'properties' => ['student_id' => ['type' => 'string'], 'student_name' => ['type' => 'string'], 'date' => ['type' => 'string']]],
                'handler' => 'studentAttendance',
            ],
            'get_student_conduct' => [
                'description' => 'Lấy hạnh kiểm/rèn luyện của học sinh.',
                'parameters' => ['type' => 'object', 'properties' => ['student_id' => ['type' => 'string'], 'student_name' => ['type' => 'string'], 'semester' => ['type' => 'string']]],
                'handler' => 'studentConduct',
            ],
            'get_child_tuition' => [
                'description' => 'Lấy học phí, khoản thu, trạng thái đóng tiền và mã QR thanh toán của học sinh/con.',
                'parameters' => ['type' => 'object', 'properties' => ['student_id' => ['type' => 'string'], 'student_name' => ['type' => 'string'], 'semester' => ['type' => 'string']]],
                'handler' => 'childTuition',
            ],
            'get_student_exam_schedule' => [
                'description' => 'Lấy lịch kiểm tra/lịch thi của học sinh hoặc lớp.',
                'parameters' => ['type' => 'object', 'properties' => ['student_id' => ['type' => 'string'], 'student_name' => ['type' => 'string'], 'class_name' => ['type' => 'string'], 'subject_name' => ['type' => 'string']]],
                'handler' => 'studentExamSchedule',
            ],
            'get_homeroom_leave_requests' => [
                'description' => 'Lấy danh sách đơn xin nghỉ của lớp chủ nhiệm hoặc lớp được admin chọn.',
                'parameters' => ['type' => 'object', 'properties' => ['class_name' => ['type' => 'string'], 'status' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected', 'all']]]],
                'handler' => 'homeroomLeaveRequests',
            ],
            'get_teacher_substitute_schedule' => [
                'description' => 'Lấy lịch dạy thay hoặc đổi tiết của giáo viên theo ngày/khoảng ngày.',
                'parameters' => ['type' => 'object', 'properties' => ['teacher_name' => ['type' => 'string'], 'date' => ['type' => 'string'], 'from_date' => ['type' => 'string'], 'to_date' => ['type' => 'string']]],
                'handler' => 'teacherSubstituteSchedule',
            ],
            'get_announcements_documents' => [
                'description' => 'Lấy thông báo, sự kiện hoặc tài liệu học tập được phép hiển thị cho người dùng.',
                'parameters' => ['type' => 'object', 'properties' => ['type' => ['type' => 'string', 'enum' => ['announcements', 'events', 'documents', 'all']], 'subject_name' => ['type' => 'string'], 'class_name' => ['type' => 'string']]],
                'handler' => 'announcementsDocuments',
            ],
            'get_score_calculation_rules' => [
                'description' => 'Lấy cấu hình trọng số và công thức tính điểm thật của hệ thống, dùng khi hỏi cách tính điểm hoặc điểm trung bình.',
                'parameters' => ['type' => 'object', 'properties' => ['grade_level' => ['type' => 'integer'], 'subject_name' => ['type' => 'string']]],
                'handler' => 'scoreCalculationRules',
            ],
            'get_system_navigation' => [
                'description' => 'Tra cứu vị trí chức năng/menu/route thật trong hệ thống theo vai trò người dùng, dùng khi hỏi vào đâu để thao tác.',
                'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']]],
                'handler' => 'systemNavigation',
            ],
        ];
    }

    public function exists(string $name): bool
    {
        return array_key_exists($name, $this->all());
    }

    public function get(string $name): ?array
    {
        return $this->all()[$name] ?? null;
    }

    public function declarations(): array
    {
        return collect($this->all())
            ->map(fn (array $tool, string $name) => [
                'name' => $name,
                'description' => $tool['description'],
                'parameters' => $tool['parameters'],
            ])
            ->values()
            ->all();
    }
}
