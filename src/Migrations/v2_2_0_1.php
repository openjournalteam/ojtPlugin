<?php

namespace Openjournalteam\OjtPlugin\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class v2_2_0_1 extends Migration
{
    const JOB_TRACKING_TABLE = 'ojt_job_tracking';
    const FAILED_JOBS_TABLE = 'ojt_failed_jobs';

    /**
     * Add the lifecycle data required by the Background Jobs panel.
     */
    public function up()
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable(self::JOB_TRACKING_TABLE)) {
            $schema->create(self::JOB_TRACKING_TABLE, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('queue_job_id')->nullable();
                $table->unsignedBigInteger('failed_job_id')->nullable();
                $table->string('tracking_token', 64)->nullable();
                $table->unsignedInteger('context_id')->nullable();
                $table->string('queue', 191)->default('default');
                $table->string('job_type', 191);
                $table->string('display_name', 255);
                $table->longText('details')->nullable();
                $table->string('status', 32)->default('queued');
                $table->unsignedTinyInteger('progress')->default(0);
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedTinyInteger('max_attempts')->nullable();
                $table->unsignedInteger('available_at')->nullable();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('paused_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamp('stopped_at')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->timestamp('cancel_requested_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['context_id', 'status']);
                $table->index(['queue', 'status']);
                $table->index(['queue_job_id']);
                $table->index(['failed_job_id']);
                $table->index(['tracking_token']);
                $table->index(['created_at']);
            });
        } elseif (!$schema->hasColumn(self::JOB_TRACKING_TABLE, 'tracking_token')) {
            $schema->table(self::JOB_TRACKING_TABLE, function (Blueprint $table) {
                $table->string('tracking_token', 64)->nullable()->after('failed_job_id');
                $table->index(['tracking_token']);
            });
        }

        if ($schema->hasTable(self::JOB_TRACKING_TABLE)
            && !$schema->hasColumn(self::JOB_TRACKING_TABLE, 'details')) {
            $schema->table(self::JOB_TRACKING_TABLE, function (Blueprint $table) {
                $table->longText('details')->nullable();
            });
        }

        // Keep the failed-job record linked to the queue row that produced it.
        if ($schema->hasTable(self::FAILED_JOBS_TABLE)
            && !$schema->hasColumn(self::FAILED_JOBS_TABLE, 'queue_job_id')) {
            $schema->table(self::FAILED_JOBS_TABLE, function (Blueprint $table) {
                $table->unsignedBigInteger('queue_job_id')->nullable()->after('queue');
                $table->index(['queue_job_id']);
            });
        }
    }

    public function down()
    {
        $schema = Capsule::schema();

        if ($schema->hasTable(self::FAILED_JOBS_TABLE)
            && $schema->hasColumn(self::FAILED_JOBS_TABLE, 'queue_job_id')) {
            $schema->table(self::FAILED_JOBS_TABLE, function (Blueprint $table) {
                $table->dropIndex(['queue_job_id']);
                $table->dropColumn('queue_job_id');
            });
        }

        $schema->dropIfExists(self::JOB_TRACKING_TABLE);
    }
}
