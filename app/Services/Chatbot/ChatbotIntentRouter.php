<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatbotIntentRouter
{
    public function __construct(private readonly GeminiIntentClassifier $gemini)
    {
    }

    public function resolve(User $user, string $question, Collection $history): ParsedChatbotIntent
    {
        $geminiIntent = $this->gemini->classify($user, $question, $history);

        if ($geminiIntent && ! $geminiIntent->isUnknown() && ! $geminiIntent->needsClarification()) {
            return $geminiIntent;
        }

        if ($geminiIntent?->needsClarification()) {
            return $geminiIntent;
        }

        return $this->resolveLocally($user, $question, $history);
    }

    private function resolveLocally(User $user, string $question, Collection $history): ParsedChatbotIntent
    {
        $normalized = $this->normalize($question);

        if ($this->isGeneralConversation($normalized) || $this->isGeneralKnowledgeQuestion($normalized)) {
            return new ParsedChatbotIntent('general', [], 0.8, 'local');
        }

        if ($user->isParent() && $this->looksLikeChildSelection($normalized, $history)) {
            $previous = $this->previousDataIntent($history);
            if ($previous) {
                return new ParsedChatbotIntent($previous, ['student' => $question, 'student_scope' => 'my_child'], 0.75, 'local_context');
            }
        }

        $entities = $this->localEntities($normalized, $question);

        foreach ($this->orderedRules($user) as $intent => $rules) {
            foreach ($rules as $rule) {
                if ($this->matchesRule($normalized, $rule)) {
                    return new ParsedChatbotIntent($intent, $entities, 0.74, 'local');
                }
            }
        }

        if ($this->isFollowUp($normalized)) {
            $previous = $this->previousDataIntent($history);
            if ($previous) {
                return new ParsedChatbotIntent($previous, $entities, 0.7, 'local_context');
            }
        }

        if (str_contains($normalized, 'tinh hinh lop') || str_contains($normalized, 'thong tin lop')) {
            return new ParsedChatbotIntent(
                'unknown',
                $entities,
                0.4,
                'local',
                'Bạn muốn xem thông tin nào của lớp: sĩ số, điểm, điểm danh hay hạnh kiểm?'
            );
        }

        return new ParsedChatbotIntent('unknown', $entities, 0.0, 'local');
    }

    private function orderedRules(User $user): array
    {
        $scoreIntent = $user->isParent() ? 'child_score' : 'student_score';
        $averageIntent = $user->isParent() ? 'child_average' : 'student_average';
        $attendanceIntent = $user->isParent() ? 'child_attendance' : 'student_attendance';
        $conductIntent = $user->isParent() ? 'child_conduct' : 'student_conduct';
        $timetableIntent = $user->isParent() ? 'child_timetable' : ($user->isTeacher() ? 'teacher_timetable' : 'student_timetable');
        $tuitionIntent = $user->isParent() ? 'child_tuition' : 'student_tuition';

        return [
            'student_count_by_class' => [
                ['all' => ['hoc sinh', 'lop'], 'any' => ['moi lop', 'tung lop', 'theo lop', 'si so tung lop', 'lop nao co bao nhieu']],
                ['all' => ['si so', 'lop'], 'any' => ['moi', 'tung', 'theo']],
                ['all' => ['may em', 'lop'], 'any' => ['moi lop', 'tung lop']],
            ],
            'largest_class' => [
                ['all' => ['lop'], 'any' => ['dong hoc sinh nhat', 'nhieu hoc sinh nhat', 'si so cao nhat']],
            ],
            'smallest_class' => [
                ['all' => ['lop'], 'any' => ['it hoc sinh nhat', 'si so thap nhat', 'it nhat']],
            ],
            'teacher_count_by_department' => [
                ['all' => ['giao vien'], 'any' => ['moi to', 'tung to', 'theo to', 'thong ke giao vien theo to']],
                ['all' => ['thay co'], 'any' => ['moi to', 'tung to', 'theo to']],
            ],
            'class_count_by_teacher' => [
                ['all' => ['giao vien'], 'any' => ['moi giao vien', 'tung giao vien'], 'any2' => ['bao nhieu lop', 'may lop', 'so lop']],
            ],
            'child_homeroom_teacher' => [
                ['all' => ['con'], 'any' => ['gvcn', 'chu nhiem', 'giao vien chu nhiem']],
            ],
            'teachers_by_class' => [
                ['all' => ['giao vien', 'lop'], 'any' => ['ai day', 'nao day', 'dang day', 'phu trach']],
                ['all' => ['thay co', 'lop'], 'any' => ['ai day', 'nao day', 'dang day']],
            ],
            'class_student_count' => [
                ['all' => ['lop'], 'any' => ['bao nhieu hoc sinh', 'may hoc sinh', 'bao nhieu em', 'si so']],
            ],
            'count_students' => [
                ['any' => ['tong so hoc sinh', 'co bao nhieu hoc sinh', 'may hoc sinh', 'so luong hoc sinh']],
            ],
            'count_teachers' => [
                ['any' => ['tong so giao vien', 'co bao nhieu giao vien', 'may giao vien', 'so luong giao vien', 'bao nhieu thay co']],
            ],
            'count_classes' => [
                ['any' => ['tong so lop', 'co bao nhieu lop', 'may lop hoc', 'so luong lop']],
            ],
            'students_low_score' => [
                ['any' => ['duoi 5 diem', 'duoi nam diem', 'ket qua thap', 'hoc sinh co nguy co']],
            ],
            'students_missing_score' => [
                ['any' => ['chua co diem', 'chua nhap diem', 'thieu diem']],
            ],
            'class_average_score' => [
                ['all' => ['lop'], 'any' => ['diem trung binh', 'trung binh lop']],
            ],
            'teacher_classes' => [
                ['any' => ['toi dang day bao nhieu lop', 'toi day bao nhieu lop', 'toi day lop nao', 'cac lop toi dang day', 'day nhung lop nao']],
            ],
            'teacher_student_count' => [
                ['any' => ['toi dang day bao nhieu hoc sinh', 'toi day bao nhieu hoc sinh', 'tong cong bao nhieu hoc sinh']],
            ],
            'teacher_subjects' => [
                ['any' => ['toi dang day mon nao', 'mon nao toi dang day', 'nhung mon toi dang day']],
            ],
            $timetableIntent => [
                ['any' => ['lich day', 'lich hoc', 'thoi khoa bieu', 'tkb', 'ngay mai toi day', 'mai toi day', 'ngay mai toi hoc', 'hom nay toi hoc', 'hom nay toi day', 'co tiet gi', 'dung lop']],
            ],
            $averageIntent => [
                ['all' => ['trung binh'], 'any' => ['diem', 'ket qua', 'hoc luc']],
            ],
            $scoreIntent => [
                ['any' => ['diem', 'ket qua', 'hoc luc'], 'not' => ['diem danh']],
            ],
            $attendanceIntent => [
                ['any' => ['diem danh', 'vang', 'nghi hoc', 'nghi bao nhieu', 'chuyen can', 'di muon']],
            ],
            $conductIntent => [
                ['any' => ['hanh kiem', 'ren luyen']],
            ],
            $tuitionIntent => [
                ['any' => ['hoc phi', 'khoan thu', 'tien hoc', 'con no', 'chua dong', 'da dong']],
            ],
            $user->isParent() ? 'child_class' : 'my_class' => [
                ['any' => ['hoc lop nao', 'lop nao', 'lop cua con', 'con toi hoc lop']],
            ],
            'student_exam_schedule' => [
                ['any' => ['lich kiem tra', 'lich thi', 'sap kiem tra', 'thi mon']],
            ],
            'announcements' => [
                ['any' => ['thong bao', 'tin tuc', 'su kien']],
            ],
            'documents' => [
                ['any' => ['tai lieu', 'hoc lieu', 'file hoc tap']],
            ],
            'homeroom_leave_requests' => [
                ['any' => ['don xin nghi', 'phu huynh gui don', 'cho duyet', 'chua xu ly']],
            ],
            'department_detail' => [
                ['any' => ['to chuyen mon', 'giao vien trong to', 'mon cua to', 'to truong']],
            ],
        ];
    }

    private function matchesRule(string $normalized, array $rule): bool
    {
        if (isset($rule['all']) && ! collect($rule['all'])->every(fn ($pattern) => str_contains($normalized, $pattern))) {
            return false;
        }

        if (isset($rule['any']) && ! collect($rule['any'])->contains(fn ($pattern) => str_contains($normalized, $pattern))) {
            return false;
        }

        if (isset($rule['any2']) && ! collect($rule['any2'])->contains(fn ($pattern) => str_contains($normalized, $pattern))) {
            return false;
        }

        if (isset($rule['not']) && collect($rule['not'])->contains(fn ($pattern) => str_contains($normalized, $pattern))) {
            return false;
        }

        return true;
    }

    private function localEntities(string $normalized, string $question): array
    {
        $entities = [];

        if (preg_match('/\b(1[0-2][a-z][0-9]|[0-9]{2}[a-z][0-9])\b/i', $question, $matches)) {
            $entities['class'] = strtoupper($matches[1]);
        }

        foreach (['hom nay' => 'today', 'ngay mai' => 'tomorrow', 'mai' => 'tomorrow', 'tuan nay' => 'this_week', 'thang nay' => 'this_month'] as $needle => $date) {
            if (str_contains($normalized, $needle)) {
                $entities['date'] = $date;
                break;
            }
        }

        if (preg_match('/\b(hk\s*1|hoc ky 1|ky 1)\b/u', $normalized)) {
            $entities['semester'] = 'HK1';
        } elseif (preg_match('/\b(hk\s*2|hoc ky 2|ky 2)\b/u', $normalized)) {
            $entities['semester'] = 'HK2';
        }

        foreach (['toan', 'ngu van', 'van', 'tieng anh', 'anh', 'vat ly', 'ly', 'hoa', 'sinh', 'lich su', 'su', 'dia ly', 'dia'] as $subject) {
            if (str_contains($normalized, $subject)) {
                $entities['subject'] = $subject;
                break;
            }
        }

        if (str_contains($normalized, 'con toi') || str_contains($normalized, 'be nha toi') || str_contains($normalized, 'con')) {
            $entities['student_scope'] = 'my_child';
        } elseif (str_contains($normalized, 'toi') || str_contains($normalized, 'cua toi')) {
            $entities['student_scope'] = 'self';
        }

        return $entities;
    }

    private function previousDataIntent(Collection $history): ?string
    {
        foreach ($history->reverse() as $message) {
            if (! $message instanceof ChatbotMessage) {
                continue;
            }

            $previous = $this->resolveLocallyForHistory((string) $message->question);
            if ($previous && $previous !== 'general' && $previous !== 'unknown') {
                return $previous;
            }
        }

        return null;
    }

    private function resolveLocallyForHistory(string $question): ?string
    {
        $normalized = $this->normalize($question);

        foreach ($this->orderedRules(new User(['role' => 'parent', 'role_type' => 'parent'])) as $intent => $rules) {
            foreach ($rules as $rule) {
                if ($this->matchesRule($normalized, $rule)) {
                    return (string) $intent;
                }
            }
        }

        return null;
    }

    private function looksLikeChildSelection(string $normalized, Collection $history): bool
    {
        return $history->isNotEmpty()
            && mb_strlen($normalized) <= 45
            && ! preg_match('/\b(diem|lich|hoc phi|vang|nghi|lop|bao nhieu|thong ke|giao vien)\b/u', $normalized);
    }

    private function isFollowUp(string $normalized): bool
    {
        return mb_strlen($normalized) <= 45
            && (bool) preg_match('/\b(con|the con|cai do|mon do|lop do|van|toan|anh|ly|hoa|sinh|su|dia|tiep)\b/u', $normalized);
    }

    private function isGeneralConversation(string $normalized): bool
    {
        return (bool) preg_match('/^(xin chao|chao|hello|hi|hey|ban la ai|ban co the giup gi|tro ly la ai|cam on|thank you|thanks)(\b|$)/u', $normalized);
    }

    private function isGeneralKnowledgeQuestion(string $normalized): bool
    {
        if (preg_match('/^\s*[\d\s+\-*\/().,=?:]+$/u', $normalized)) {
            return true;
        }

        return (bool) preg_match('/\b(la gi|nghia la gi|tai sao|nhu the nao|may gio|hom nay ban|ban thay|ke toi nghe|giai thich)\b/u', $normalized);
    }

    private function normalize(string $text): string
    {
        return Str::of($text)->ascii()->lower()->squish()->toString();
    }
}
