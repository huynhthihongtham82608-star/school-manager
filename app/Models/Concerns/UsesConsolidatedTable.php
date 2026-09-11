<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait UsesConsolidatedTable
{
    protected function usesConsolidatedTable(string $targetTable, string $sourceTable, string $flagColumn): bool
    {
        return Schema::hasColumn($targetTable, $flagColumn)
            && (! static::baseTableExists($sourceTable) || static::baseTableIsEmpty($sourceTable));
    }

    protected static function shouldScopeConsolidatedTable(string $targetTable, string $sourceTable, string $flagColumn): bool
    {
        return Schema::hasColumn($targetTable, $flagColumn)
            && (! static::baseTableExists($sourceTable) || static::baseTableIsEmpty($sourceTable));
    }

    protected static function baseTableIsEmpty(string $table): bool
    {
        return static::baseTableExists($table) && DB::table($table)->doesntExist();
    }

    protected static function baseTableExists(string $table): bool
    {
        $result = DB::selectOne(
            "SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );

        return $result && strtoupper((string) $result->TABLE_TYPE) === 'BASE TABLE';
    }
}
