<?php

namespace Openjournalteam\OjtPlugin\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class v2_2_0_0 extends Migration
{
    const JOBS_TABLE = 'ojt_jobs';
    const FAILED_JOBS_TABLE = 'ojt_failed_jobs';

    /**
     * Run migration.
     */
    public function up()
    {
        $schema = Capsule::schema();
        if (!$schema->hasTable(self::JOBS_TABLE)) {
            $schema->create(self::JOBS_TABLE, function (Blueprint $table) {
                // Keep same structure as Laravel "jobs" table for DB queue driver.
                $table->bigIncrements('id');
                $table->string('queue');
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('context_id')->nullable();
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');

                $table->index(['queue', 'reserved_at']);
                $table->index(['context_id']);
            });
        } elseif (!$schema->hasColumn(self::JOBS_TABLE, 'context_id')) {
            // Upgrade existing jobs table.
            $schema->table(self::JOBS_TABLE, function (Blueprint $table) {
                $table->unsignedInteger('context_id')->nullable()->after('attempts');
                $table->index(['context_id']);
            });
        }

        if (!$schema->hasTable(self::FAILED_JOBS_TABLE)) {
            $schema->create(self::FAILED_JOBS_TABLE, function (Blueprint $table) {
                // Laravel-like failed jobs structure + context for journal isolation.
                $table->bigIncrements('id');
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->unsignedInteger('context_id')->nullable();
                $table->timestamp('failed_at')->useCurrent();

                $table->index(['context_id']);
            });
        } elseif (!$schema->hasColumn(self::FAILED_JOBS_TABLE, 'context_id')) {
            $schema->table(self::FAILED_JOBS_TABLE, function (Blueprint $table) {
                $table->unsignedInteger('context_id')->nullable()->after('exception');
                $table->index(['context_id']);
            });
        }
    }

    /**
     * Reverse migration.
     */
    public function down()
    {
        Capsule::schema()->dropIfExists(self::FAILED_JOBS_TABLE);
        Capsule::schema()->dropIfExists(self::JOBS_TABLE);
    }
}