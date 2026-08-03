<?php

use App\Models\ScoreSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('score_settings')) {
            Schema::create('score_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('weight_gdtx')->default(ScoreSetting::DEFAULT_WEIGHT_GDTX);
                $table->unsignedTinyInteger('weight_dggk')->default(ScoreSetting::DEFAULT_WEIGHT_DGGK);
                $table->unsignedTinyInteger('weight_dgck')->default(ScoreSetting::DEFAULT_WEIGHT_DGCK);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('score_settings') && DB::table('score_settings')->doesntExist()) {
            DB::table('score_settings')->insert([
                'weight_gdtx' => ScoreSetting::DEFAULT_WEIGHT_GDTX,
                'weight_dggk' => ScoreSetting::DEFAULT_WEIGHT_DGGK,
                'weight_dgck' => ScoreSetting::DEFAULT_WEIGHT_DGCK,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('score_settings');
    }
};
