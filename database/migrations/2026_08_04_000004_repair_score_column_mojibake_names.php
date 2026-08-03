<?php

use App\Models\ScoreColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('score_columns')) {
            return;
        }

        DB::transaction(function () {
            $columns = DB::table('score_columns')
                ->orderBy('school_year_id')
                ->orderBy('grade_level')
                ->orderBy('subject_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $groups = $columns->groupBy(fn ($column) => implode('|', [
                $column->school_year_id,
                $column->grade_level,
                $column->subject_id,
                $this->familyFor($column),
            ]));

            $groups->each(function (Collection $familyColumns) {
                $family = $this->familyFor($familyColumns->first());
                $baseName = $this->baseNameFor($family);

                if (! $baseName) {
                    return;
                }

                $managedColumns = $familyColumns
                    ->sortBy(fn ($column) => [$this->sequenceFor($column), $column->sort_order, $column->created_at, $column->id])
                    ->values();
                $count = $managedColumns->count();

                $managedColumns->each(function ($column, int $index) use ($baseName, $count) {
                    $name = $count > 1 && in_array($baseName, ['Kiểm tra Miệng', 'Kiểm tra 15 phút'], true)
                        ? "{$baseName} (Lần " . ($index + 1) . ')'
                        : $baseName;

                    if ($column->name === $name) {
                        return;
                    }

                    DB::table('score_columns')
                        ->where('id', $column->id)
                        ->update([
                            'name' => $name,
                            'updated_at' => now(),
                        ]);

                    if (Schema::hasTable('score_details') && Schema::hasColumn('score_details', 'score_column_id')) {
                        DB::table('score_details')
                            ->where('score_column_id', $column->id)
                            ->update([
                                'name' => $name,
                                'updated_at' => now(),
                            ]);
                    }
                });
            });
        });
    }

    public function down(): void
    {
        //
    }

    private function familyFor($column): string
    {
        if ($column->type === ScoreColumn::TYPE_MIDTERM) {
            return 'midterm';
        }

        if ($column->type === ScoreColumn::TYPE_FINAL) {
            return 'final';
        }

        $name = Str::lower(Str::ascii((string) $column->name));

        if (str_contains($name, '15')) {
            return 'fifteen';
        }

        if (str_contains($name, 'mieng') || (int) $column->sort_order < 20) {
            return 'oral';
        }

        return 'one_period';
    }

    private function baseNameFor(string $family): ?string
    {
        return match ($family) {
            'oral' => 'Kiểm tra Miệng',
            'fifteen' => 'Kiểm tra 15 phút',
            'one_period' => 'Kiểm tra 1 tiết',
            'midterm' => 'Kiểm tra Giữa kỳ',
            'final' => 'Kiểm tra Cuối kỳ',
            default => null,
        };
    }

    private function sequenceFor($column): int
    {
        $name = Str::lower(Str::ascii((string) $column->name));

        if (preg_match('/lan\s*(\d+)/', $name, $matches)) {
            return (int) $matches[1];
        }

        return max(1, (int) $column->sort_order);
    }
};
