<?php

namespace Openjournalteam\OjtPlugin\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class v2_2_0_1 extends Migration
{
    const JOB_TRACKING_TABLE = 'ojt_job_tracking';
    const FAILED_JOBS_TABLE = 'ojt_failed_jobs';
    const SCHEDULES_TABLE = 'ojt_schedules';
    const SCHEDULE_RUNS_TABLE = 'ojt_schedule_runs';

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

        if (!$schema->hasTable(self::SCHEDULES_TABLE)) {
            $schema->create(self::SCHEDULES_TABLE, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('schedule_key', 191);
                $table->unsignedInteger('context_id')->default(0);
                $table->string('display_name', 255);
                $table->string('job_type', 191);
                $table->string('cron_expression', 100);
                $table->string('timezone', 64)->default('UTC');
                $table->boolean('enabled')->default(false);
                $table->longText('payload')->nullable();
                $table->longText('options')->nullable();
                $table->dateTime('next_run_at')->nullable();
                $table->dateTime('last_run_at')->nullable();
                $table->unsignedTinyInteger('dispatch_attempts')->default(0);
                $table->string('last_status', 32)->nullable();
                $table->string('last_job_id', 120)->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->unique(['schedule_key', 'context_id'], 'ojt_schedules_key_context');
                $table->index(['enabled', 'next_run_at'], 'ojt_schedules_due');
                $table->index(['context_id', 'enabled'], 'ojt_schedules_context_enabled');
            });
        }

        if ($schema->hasTable(self::SCHEDULES_TABLE)
            && !$schema->hasColumn(self::SCHEDULES_TABLE, 'dispatch_attempts')) {
            $schema->table(self::SCHEDULES_TABLE, function (Blueprint $table) {
                $table->unsignedTinyInteger('dispatch_attempts')->default(0)->after('last_run_at');
            });
        }

        if (!$schema->hasTable(self::SCHEDULE_RUNS_TABLE)) {
            $schema->create(self::SCHEDULE_RUNS_TABLE, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('schedule_id');
                $table->unsignedInteger('context_id')->default(0);
                $table->string('trigger_type', 32)->default('scheduled');
                $table->unsignedBigInteger('queue_job_id')->nullable();
                $table->string('status', 32)->default('dispatching');
                $table->unsignedTinyInteger('progress')->default(0);
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->longText('details')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamp('stopped_at')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->timestamps();

                $table->index(['schedule_id', 'created_at'], 'ojt_schedule_runs_schedule_created');
                $table->index(['schedule_id', 'status'], 'ojt_schedule_runs_schedule_status');
                $table->index(['queue_job_id']);
                $table->index(['context_id', 'created_at']);
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

        if ($schema->hasTable(self::FAILED_JOBS_TABLE)
            && !$schema->hasColumn(self::FAILED_JOBS_TABLE, 'retry_claimed_at')) {
            $schema->table(self::FAILED_JOBS_TABLE, function (Blueprint $table) {
                $table->timestamp('retry_claimed_at')->nullable()->after('failed_at');
                $table->index(['retry_claimed_at']);
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

        if ($schema->hasTable(self::FAILED_JOBS_TABLE)
            && $schema->hasColumn(self::FAILED_JOBS_TABLE, 'retry_claimed_at')) {
            $schema->table(self::FAILED_JOBS_TABLE, function (Blueprint $table) {
                $table->dropIndex(['retry_claimed_at']);
                $table->dropColumn('retry_claimed_at');
            });
        }

        $schema->dropIfExists(self::JOB_TRACKING_TABLE);
        $schema->dropIfExists(self::SCHEDULES_TABLE);
        $schema->dropIfExists(self::SCHEDULE_RUNS_TABLE);
    }
}
