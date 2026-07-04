<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->consolidateName('members');
        $this->consolidateName('employees');
    }

    public function down(): void
    {
        $this->restoreNameParts('members');
        $this->restoreNameParts('employees');
    }

    private function consolidateName(string $table): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'name')) {
            return;
        }

        $hasFirstName = Schema::hasColumn($table, 'first_name');
        $hasLastName = Schema::hasColumn($table, 'last_name');

        if (!$hasFirstName && !$hasLastName) {
            return;
        }

        $columns = array_values(array_filter([
            'id',
            'name',
            $hasFirstName ? 'first_name' : null,
            $hasLastName ? 'last_name' : null,
        ]));

        DB::table($table)
            ->select($columns)
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $hasFirstName, $hasLastName): void {
                foreach ($rows as $row) {
                    $firstName = $hasFirstName ? trim((string) ($row->first_name ?? '')) : '';
                    $lastName = $hasLastName ? trim((string) ($row->last_name ?? '')) : '';
                    $name = trim($firstName . ' ' . $lastName);

                    if ($name === '') {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['name' => $name]);
                }
            });

        Schema::table($table, function (Blueprint $tableBlueprint) use ($table, $hasFirstName, $hasLastName): void {
            $columns = [];

            if ($hasFirstName && Schema::hasColumn($table, 'first_name')) {
                $columns[] = 'first_name';
            }

            if ($hasLastName && Schema::hasColumn($table, 'last_name')) {
                $columns[] = 'last_name';
            }

            if ($columns !== []) {
                $tableBlueprint->dropColumn($columns);
            }
        });
    }

    private function restoreNameParts(string $table): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'name')) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($table): void {
            if (!Schema::hasColumn($table, 'first_name')) {
                $tableBlueprint->string('first_name')->nullable()->after('name');
            }

            if (!Schema::hasColumn($table, 'last_name')) {
                $tableBlueprint->string('last_name')->nullable()->after('first_name');
            }
        });

        DB::table($table)
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $parts = preg_split('/\s+/', trim((string) ($row->name ?? '')), 2);

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'first_name' => $parts[0] ?? null,
                            'last_name' => $parts[1] ?? null,
                        ]);
                }
            });
    }
};
