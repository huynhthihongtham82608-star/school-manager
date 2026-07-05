<?php

use App\Models\Room;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('type', 30)->default(Room::TYPE_STANDARD);
                $table->string('custom_type')->nullable();
                $table->unsignedSmallInteger('capacity')->default(45);
                $table->string('status', 30)->default(Room::STATUS_ACTIVE)->index();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('timetable_entries') && ! Schema::hasColumn('timetable_entries', 'room_id')) {
            Schema::table('timetable_entries', function (Blueprint $table) {
                $table->string('room_id', 50)->nullable()->after('room')->index();
            });
        }

        if (Schema::hasTable('timetable_entries')) {
            DB::table('timetable_entries')
                ->whereNotNull('room')
                ->where('room', '!=', '')
                ->select('room')
                ->distinct()
                ->orderBy('room')
                ->get()
                ->each(function ($entry) {
                    $name = trim((string) $entry->room);

                    if ($name === '') {
                        return;
                    }

                    $roomId = DB::table('rooms')->where('name', $name)->value('id');

                    if (! $roomId) {
                        $roomId = (string) Str::uuid();
                        DB::table('rooms')->insert([
                            'id' => $roomId,
                            'name' => $name,
                            'type' => Room::TYPE_STANDARD,
                            'capacity' => 45,
                            'status' => Room::STATUS_ACTIVE,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('timetable_entries')
                        ->where('room', $name)
                        ->whereNull('room_id')
                        ->update(['room_id' => $roomId]);
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('timetable_entries') && Schema::hasColumn('timetable_entries', 'room_id')) {
            Schema::table('timetable_entries', function (Blueprint $table) {
                $table->dropColumn('room_id');
            });
        }

        Schema::dropIfExists('rooms');
    }
};
