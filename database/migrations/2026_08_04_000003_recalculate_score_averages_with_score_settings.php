<?php

use App\Models\ScoreColumn;
use App\Models\ScoreSetting;
use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('score_settings')
            || ! Schema::hasTable('score_headers')
            || ! Schema::hasTable('score_details')
            || ! Schema::hasTable('subjects')
        ) {
            return;
        }

        $setting = DB::table('score_settings')->first();
        $weightGdtx = max(1, (int) ($setting?->weight_gdtx ?? ScoreSetting::DEFAULT_WEIGHT_GDTX));
        $weightDggk = max(1, (int) ($setting?->weight_dggk ?? ScoreSetting::DEFAULT_WEIGHT_DGGK));
        $weightDgck = max(1, (int) ($setting?->weight_dgck ?? ScoreSetting::DEFAULT_WEIGHT_DGCK));

        DB::table('score_headers')
            ->join('subjects', 'subjects.id', '=', 'score_headers.subject_id')
            ->select([
                'score_headers.id',
                'subjects.assessment_type',
            ])
            ->orderBy('score_headers.id')
            ->chunk(100, function ($headers) use ($weightGdtx, $weightDggk, $weightDgck) {
                foreach ($headers as $header) {
                    $assessmentType = Subject::normalizeAssessmentType($header->assessment_type);
                    if ($assessmentType !== Subject::ASSESSMENT_GRADE_10) {
                        DB::table('score_headers')->where('id', $header->id)->update(['average' => null]);
                        continue;
                    }

                    $details = DB::table('score_details')
                        ->where('score_header_id', $header->id)
                        ->whereNotNull('value')
                        ->get(['type', 'value']);

                    $weightedSum = 0;
                    $totalWeight = 0;

                    foreach ($details as $detail) {
                        $weight = match ($detail->type) {
                            ScoreColumn::TYPE_MIDTERM, 'midterm', 'midterm_test' => $weightDggk,
                            ScoreColumn::TYPE_FINAL, 'final', 'final_test' => $weightDgck,
                            default => $weightGdtx,
                        };

                        $weightedSum += (float) $detail->value * $weight;
                        $totalWeight += $weight;
                    }

                    DB::table('score_headers')->where('id', $header->id)->update([
                        'average' => $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : null,
                    ]);
                }
            });
    }

    public function down(): void
    {
    }
};
