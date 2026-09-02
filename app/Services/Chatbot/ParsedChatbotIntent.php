<?php

namespace App\Services\Chatbot;

class ParsedChatbotIntent
{
    public function __construct(
        public readonly string $intent,
        public readonly array $entities = [],
        public readonly float $confidence = 0.0,
        public readonly string $source = 'local',
        public readonly ?string $clarification = null,
    ) {
    }

    public function isUnknown(): bool
    {
        return $this->intent === 'unknown';
    }

    public function needsClarification(): bool
    {
        return filled($this->clarification);
    }

    public function legacyIntent(): string
    {
        return match ($this->intent) {
            'student_score',
            'child_score',
            'student_average',
            'child_average',
            'class_average_score',
            'students_missing_score',
            'students_low_score' => 'scores',

            'student_timetable',
            'child_timetable',
            'teacher_timetable' => 'timetable',

            'student_exam_schedule',
            'child_exam_schedule' => 'exams',

            'student_attendance',
            'child_attendance',
            'homeroom_attendance' => 'attendance',

            'student_conduct',
            'child_conduct',
            'homeroom_conduct' => 'conduct',

            'student_tuition',
            'child_tuition',
            'homeroom_tuition_status' => 'tuition',

            'student_documents',
            'documents' => 'documents',

            'student_announcements',
            'announcements',
            'events' => 'announcements',

            'my_class',
            'child_class',
            'teacher_classes',
            'teacher_subjects',
            'teachers_by_class',
            'child_teachers',
            'child_homeroom_teacher',
            'homeroom_class' => 'assignments',

            'count_students',
            'count_teachers',
            'count_classes',
            'student_count_by_class',
            'teacher_count_by_department',
            'students_by_class',
            'class_student_count',
            'teacher_student_count',
            'homeroom_student_count',
            'largest_class',
            'smallest_class',
            'class_count_by_teacher' => 'stats',

            'homeroom_leave_requests' => 'leave_requests',

            'department_detail' => 'department',

            default => $this->intent,
        };
    }

    public function questionForContext(string $question): string
    {
        $entityText = collect($this->entities)
            ->only(['student', 'class', 'subject', 'semester', 'school_year', 'date', 'department', 'teacher', 'student_scope'])
            ->filter(fn ($value) => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->join(' ');

        $hint = match ($this->intent) {
            'count_students' => 'bao nhieu hoc sinh',
            'count_teachers' => 'bao nhieu giao vien',
            'count_classes' => 'bao nhieu lop',
            'student_count_by_class' => 'thong ke hoc sinh theo lop si so tung lop',
            'class_student_count',
            'homeroom_student_count' => 'lop co bao nhieu hoc sinh si so',
            'largest_class' => 'lop nao dong hoc sinh nhat',
            'smallest_class' => 'lop nao it hoc sinh nhat',
            'teacher_count_by_department' => 'thong ke giao vien theo to',
            'class_count_by_teacher' => 'moi giao vien day bao nhieu lop',
            'teachers_by_class',
            'child_teachers' => 'giao vien nao day lop',
            'child_homeroom_teacher' => 'gvcn giao vien chu nhiem',
            'teacher_classes' => 'toi dang day bao nhieu lop day nhung lop nao',
            'teacher_subjects' => 'mon nao toi dang day',
            'teacher_student_count' => 'toi dang day bao nhieu hoc sinh',
            'teacher_timetable' => 'lich day ngay mai hom nay',
            'student_timetable',
            'child_timetable' => 'lich hoc thoi khoa bieu',
            'student_score',
            'child_score' => 'diem ket qua mon hoc',
            'student_average',
            'child_average',
            'class_average_score' => 'diem trung binh',
            'students_missing_score' => 'hoc sinh chua co diem chua nhap diem',
            'students_low_score' => 'hoc sinh duoi 5 diem ket qua thap',
            'student_attendance',
            'child_attendance',
            'homeroom_attendance' => 'diem danh vang nghi hoc',
            'student_tuition',
            'child_tuition',
            'homeroom_tuition_status' => 'hoc phi chua dong',
            default => '',
        };

        return trim($question . ' ' . $hint . ' ' . $entityText);
    }
}
