<?php

namespace Openjournalteam\OjtPlugin\Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use Openjournalteam\OjtPlugin\Migrations\MigrationManager;
use Throwable;

class ScheduleService
{
    const TABLE = 'ojt_schedules';
    const RUNS_TABLE = 'ojt_schedule_runs';
    const JOB_RUNS_TABLE = 'ojt_job_tracking';
    const DISPATCH_RETRY_BASE_SECONDS = 60;
    const DISPATCH_RETRY_MAX_SECONDS = 3600;
    const STALE_PROCESSING_SECONDS = 1800;
    const STALE_QUEUED_SECONDS = 3600;
    const MAX_DISPATCHES_PER_POLL = 20;
    const MAX_CRON_LENGTH = 100;
    const MAX_CRON_TERMS = 64;
    const MAX_CRON_SEARCH_DAYS = 366 * 8;

    /** @var object */
    protected $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function listSchedules($contextId = 0)
    {
        $this->ensureSchema();
        $this->syncDefinitions();

        $query = Capsule::table(self::TABLE)
            ->orderBy('enabled', 'desc')
            ->orderBy('display_name', 'asc');

        if ((int) $contextId > 0) {
            $query->where(function ($builder) use ($contextId) {
                $builder->where('context_id', '=', 0)
                    ->orWhere('context_id', '=', (int) $contextId);
            });
        }

        return array_map(function ($row) {
            return $this->formatSchedule($row);
        }, $query->get()->all());
    }

    /**
     * Return the execution history for one registered schedule.
     */
    public function history($scheduleId, $contextId = 0, $page = 1, $pageSize = 20)
    {
        $this->ensureSchema();
        $schedule = $this->findSchedule($scheduleId);
        if (!$schedule || !$this->canViewSchedule($schedule, $contextId)) {
            return ['success' => false, 'message' => 'Schedule not found.'];
        }

        $page = max(1, (int) $page);
        $pageSize = min(50, max(1, (int) $pageSize));
        $query = Capsule::table(self::RUNS_TABLE)
            ->where('schedule_id', '=', (int) $scheduleId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
        $total = (int) (clone $query)->count();
        $rows = $query->forPage($page, $pageSize)->get()->all();

        $jobIds = array_values(array_filter(array_map(function ($row) {
            return $row->queue_job_id ? (int) $row->queue_job_id : null;
        }, $rows)));
        $tracking = [];
        if (!empty($jobIds) && Capsule::schema()->hasTable(self::JOB_RUNS_TABLE)) {
            foreach (Capsule::table(self::JOB_RUNS_TABLE)->whereIn('queue_job_id', $jobIds)->get()->all() as $jobRun) {
                $tracking[(int) $jobRun->queue_job_id] = $jobRun;
            }
        }

        return [
            'success' => true,
            'runs' => array_map(function ($row) use ($tracking, $schedule) {
                $jobRun = $row->queue_job_id ? ($tracking[(int) $row->queue_job_id] ?? null) : null;
                return $this->formatRun($row, $jobRun, (string) $schedule->timezone);
            }, $rows),
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => max(1, (int) ceil($total / $pageSize)),
            ],
        ];
    }

    public function toggle($scheduleId, $enabled, $contextId = 0, $allowGlobal = false)
    {
        $this->ensureSchema();
        $row = $this->findSchedule($scheduleId);
        if (!$row || !$this->canMutateSchedule($row, $contextId, $allowGlobal)) {
            return ['success' => false, 'message' => 'Schedule not found.'];
        }

        $enabled = (bool) $enabled;
        $nextRun = $enabled
            ? $this->calculateNextRun($row->cron_expression, $row->timezone)
            : null;
        if ($enabled && !$nextRun) {
            return ['success' => false, 'message' => 'This schedule has no valid future run time.'];
        }

        Capsule::table(self::TABLE)
            ->where('id', '=', (int) $scheduleId)
            ->update([
                'enabled' => $enabled ? 1 : 0,
                'next_run_at' => $nextRun,
                'last_status' => $enabled ? $row->last_status : 'paused',
                'updated_at' => $this->now(),
            ]);

        return [
            'success' => true,
            'message' => $enabled ? 'Schedule enabled.' : 'Schedule paused.',
        ];
    }

    public function update($scheduleId, $cronExpression, $timezone = null, $contextId = 0, $allowGlobal = false)
    {
        $this->ensureSchema();
        $row = $this->findSchedule($scheduleId);
        if (!$row || !$this->canMutateSchedule($row, $contextId, $allowGlobal)) {
            return ['success' => false, 'message' => 'Schedule not found.'];
        }

        $cronExpression = trim((string) $cronExpression);
        if (!$this->isValidCron($cronExpression)) {
            return ['success' => false, 'message' => 'Invalid schedule. Please choose a supported repeat pattern.'];
        }

        $timezone = trim((string) ($timezone ?: $row->timezone ?: date_default_timezone_get()));
        try {
            new \DateTimeZone($timezone);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Invalid schedule timezone.'];
        }

        $nextRun = $row->enabled ? $this->calculateNextRun($cronExpression, $timezone) : null;
        if ($row->enabled && !$nextRun) {
            return ['success' => false, 'message' => 'This schedule has no valid future run time.'];
        }

        Capsule::table(self::TABLE)
            ->where('id', '=', (int) $scheduleId)
            ->update([
                'cron_expression' => $cronExpression,
                'timezone' => $timezone,
                'next_run_at' => $nextRun,
                'dispatch_attempts' => 0,
                'last_error' => null,
                'updated_at' => $this->now(),
            ]);

        return ['success' => true, 'message' => 'Schedule updated.'];
    }

    public function runNow($scheduleId, $contextId = 0, $allowGlobal = false)
    {
        $this->ensureSchema();

        return Capsule::connection()->transaction(function () use ($scheduleId, $contextId, $allowGlobal) {
            $row = Capsule::table(self::TABLE)
                ->where('id', '=', (int) $scheduleId)
                ->lockForUpdate()
                ->first();
            if (!$row || !$this->canMutateSchedule($row, $contextId, $allowGlobal)) {
                return ['success' => false, 'message' => 'Schedule not found.'];
            }

            $this->recoverStaleRuns($this->now(), (int) $scheduleId);
            $activeRun = Capsule::table(self::RUNS_TABLE)
                ->where('schedule_id', '=', (int) $scheduleId)
                ->whereIn('status', ['dispatching', 'queued', 'processing'])
                ->orderBy('id', 'desc')
                ->first();
            if ($activeRun) {
                return [
                    'success' => false,
                    'message' => 'This schedule already has a queued or running execution.',
                    'runId' => (int) $activeRun->id,
                ];
            }

            return $this->dispatchSchedule($row, true);
        });
    }

    public function runDueSchedules($limit = 20)
    {
        if (method_exists($this->plugin, 'areBackgroundJobsEnabled')
            && !$this->plugin->areBackgroundJobsEnabled()) {
            return ['checked' => 0, 'dispatched' => 0, 'failed' => 0, 'errors' => [], 'disabled' => true];
        }

        $this->ensureSchema();
        $this->syncDefinitions();
        $now = $this->now();
        $this->recoverStaleRuns($now);
        $summary = ['checked' => 0, 'dispatched' => 0, 'failed' => 0, 'errors' => []];

        $rows = Capsule::table(self::TABLE)
            ->where('enabled', '=', 1)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at', 'asc')
            ->limit(min(self::MAX_DISPATCHES_PER_POLL, max(1, (int) $limit)))
            ->get();

        foreach ($rows as $row) {
            $summary['checked']++;
            $activeRun = Capsule::table(self::RUNS_TABLE)
                ->where('schedule_id', '=', (int) $row->id)
                ->whereIn('status', ['dispatching', 'queued', 'processing'])
                ->exists();
            if ($activeRun) {
                continue;
            }

            $nextRun = $this->calculateNextRun($row->cron_expression, $row->timezone);
            if (!$nextRun) {
                $summary['failed']++;
                $summary['errors'][] = $row->display_name . ': unable to calculate next run.';
                Capsule::table(self::TABLE)
                    ->where('id', '=', (int) $row->id)
                    ->where('enabled', '=', 1)
                    ->where('next_run_at', '=', $row->next_run_at)
                    ->update([
                        'enabled' => 0,
                        'next_run_at' => null,
                        'last_status' => 'failed',
                        'last_error' => 'Schedule has no valid future run time.',
                        'updated_at' => $this->now(),
                    ]);
                continue;
            }

            // Claim the exact due timestamp so two Acron workers cannot dispatch
            // the same schedule occurrence.
            $claimed = Capsule::table(self::TABLE)
                ->where('id', '=', (int) $row->id)
                ->where('enabled', '=', 1)
                ->where('next_run_at', '=', $row->next_run_at)
                ->update([
                    'next_run_at' => $nextRun,
                    'last_run_at' => $now,
                    'last_status' => 'dispatching',
                    'last_error' => null,
                    'updated_at' => $now,
                ]);

            if (!$claimed) {
                continue;
            }

            $result = $this->dispatchSchedule($row, false);
            if (!empty($result['success'])) {
                $summary['dispatched']++;
                Capsule::table(self::TABLE)
                    ->where('id', '=', (int) $row->id)
                    ->update([
                        'dispatch_attempts' => 0,
                        'updated_at' => $this->now(),
                    ]);
            } else {
                $summary['failed']++;
                $summary['errors'][] = $result['message'] ?? ($row->display_name . ': dispatch failed.');
                $this->scheduleDispatchRetry($row, $nextRun, $result['message'] ?? 'Schedule dispatch failed.');
            }
        }

        return $summary;
    }

    protected function dispatchSchedule($row, $manual)
    {
        $payload = $this->decodeJson($row->payload);
        $options = $this->decodeJson($row->options);
        $options['queue'] = $options['queue'] ?? 'default';
        $options['contextId'] = isset($options['contextId']) ? (int) $options['contextId'] : (int) $row->context_id;
        $options['displayName'] = (string) $row->display_name;
        $options['maxAttempts'] = isset($options['maxAttempts']) ? max(1, (int) $options['maxAttempts']) : 3;
        $runId = 0;

        try {
            $runId = $this->createRun($row, $manual, $payload, $options);
            $payload['scheduleId'] = (int) $row->id;
            $payload['scheduleRunId'] = $runId;

            if (!class_exists('OjtJobs') || !method_exists('OjtJobs', 'dispatchByType')) {
                throw new \RuntimeException('WorkerBee job registry is unavailable.');
            }

            $jobId = \OjtJobs::dispatchByType((string) $row->job_type, $payload, $options);
            if (!$jobId) {
                throw new \RuntimeException('WorkerBee could not dispatch the scheduled job.');
            }

            $values = [
                'last_status' => $manual ? 'manual_queued' : 'queued',
                'last_job_id' => (string) $jobId,
                'last_error' => null,
                'updated_at' => $this->now(),
            ];
            if ($manual) {
                $values['last_run_at'] = $this->now();
            }
            Capsule::table(self::TABLE)
                ->where('id', '=', (int) $row->id)
                ->update($values);
            $this->markRunQueued($runId, $jobId);

            return [
                'success' => true,
                'message' => $manual ? 'Schedule dispatched.' : 'Schedule dispatched.',
                'jobId' => $jobId,
                'runId' => $runId,
            ];
        } catch (Throwable $e) {
            if ($runId > 0) {
                $this->markRunFailed($runId, $e->getMessage());
            }
            Capsule::table(self::TABLE)
                ->where('id', '=', (int) $row->id)
                ->update([
                    'last_status' => 'failed',
                    'last_error' => substr($e->getMessage(), 0, 4000),
                    'updated_at' => $this->now(),
                ]);

            error_log('Ojt schedule dispatch failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $row->display_name . ': ' . $e->getMessage()];
        }
    }

    public function markRunStarted($runId)
    {
        if (!$runId) {
            return true;
        }
        $run = Capsule::table(self::RUNS_TABLE)->where('id', '=', (int) $runId)->first();
        if (!$run) {
            return false;
        }
        if (in_array((string) $run->status, ['done', 'failed', 'paused'], true)) {
            return false;
        }
        $now = $this->now();
        $updated = Capsule::table(self::RUNS_TABLE)
            ->where('id', '=', (int) $runId)
            ->whereIn('status', ['dispatching', 'queued', 'processing'])
            ->update([
                'status' => 'processing',
                'started_at' => $run->started_at ?: $now,
                'stopped_at' => null,
                'finished_at' => null,
                'error_message' => null,
                'updated_at' => $now,
            ]);
        if ($updated) {
            $this->updateScheduleAfterRun($run->schedule_id, 'processing', null);
        }
        return (bool) $updated;
    }

    public function markRunCompleted($runId, $result = [])
    {
        if (!$runId) {
            return true;
        }
        $run = Capsule::table(self::RUNS_TABLE)->where('id', '=', (int) $runId)->first();
        if (!$run) {
            return false;
        }
        $finishedAt = $this->now();
        $updated = Capsule::table(self::RUNS_TABLE)
            ->where('id', '=', (int) $runId)
            ->whereIn('status', ['dispatching', 'queued', 'processing'])
            ->update([
                'status' => 'done',
                'progress' => 100,
                'finished_at' => $finishedAt,
                'stopped_at' => null,
                'duration_seconds' => $this->durationSeconds($run->started_at ?: $run->queued_at, $finishedAt),
                'details' => $this->mergeRunDetails($run->details, ['result' => is_array($result) ? $result : $result]),
                'error_message' => null,
                'updated_at' => $finishedAt,
            ]);
        if ($updated) {
            $this->updateScheduleAfterRun($run->schedule_id, 'done', null);
        }
        return (bool) $updated;
    }

    public function markRunFailed($runId, $message, $details = [])
    {
        if (!$runId) {
            return true;
        }
        $run = Capsule::table(self::RUNS_TABLE)->where('id', '=', (int) $runId)->first();
        if (!$run) {
            return false;
        }
        $stoppedAt = $this->now();
        $runDetails = ['error' => (string) $message];
        if ($details !== [] && $details !== null) {
            $runDetails['result'] = $details;
        }
        $updated = Capsule::table(self::RUNS_TABLE)
            ->where('id', '=', (int) $runId)
            ->whereIn('status', ['dispatching', 'queued', 'processing'])
            ->update([
                'status' => 'failed',
                'stopped_at' => $stoppedAt,
                'duration_seconds' => $this->durationSeconds($run->started_at ?: $run->queued_at, $stoppedAt),
                'error_message' => substr((string) $message, 0, 4000),
                'details' => $this->mergeRunDetails($run->details, $runDetails),
                'updated_at' => $stoppedAt,
            ]);
        if ($updated) {
            $this->updateScheduleAfterRun($run->schedule_id, 'failed', (string) $message);
        }
        return (bool) $updated;
    }

    /**
     * Release schedule runs left active by a killed worker or failed dispatch.
     * A late queue delivery is rejected by markRunStarted() once the run is
     * marked failed, so recovery cannot create a second execution silently.
     */
    protected function recoverStaleRuns($now, $scheduleId = null)
    {
        $nowTimestamp = time();
        $processingCutoff = gmdate('Y-m-d H:i:s', $nowTimestamp - self::STALE_PROCESSING_SECONDS);
        $queuedCutoff = gmdate('Y-m-d H:i:s', $nowTimestamp - self::STALE_QUEUED_SECONDS);
        $query = Capsule::table(self::RUNS_TABLE)
            ->where(function ($builder) use ($processingCutoff, $queuedCutoff) {
                $builder->where(function ($nested) use ($processingCutoff) {
                    $nested->where('status', '=', 'processing')
                        ->where('updated_at', '<', $processingCutoff);
                })->orWhere(function ($nested) use ($queuedCutoff) {
                    $nested->whereIn('status', ['dispatching', 'queued'])
                        ->where('updated_at', '<', $queuedCutoff);
                });
            });
        if ($scheduleId !== null) {
            $query->where('schedule_id', '=', (int) $scheduleId);
        }

        foreach ($query->limit(50)->get() as $run) {
            if ($run->queue_job_id) {
                $queueJob = Capsule::table(JobQueueService::JOBS_TABLE)
                    ->where('id', '=', (int) $run->queue_job_id)
                    ->first();
                if ($queueJob && $queueJob->reserved_at === null) {
                    Capsule::table(JobQueueService::JOBS_TABLE)
                        ->where('id', '=', (int) $run->queue_job_id)
                        ->delete();
                }
            }
            $this->markRunFailed(
                (int) $run->id,
                'Schedule execution became stale and was recovered automatically.'
            );
        }
    }

    protected function syncDefinitions()
    {
        $definitions = [];
        if (class_exists('HookRegistry')) {
            \HookRegistry::call('OjtPlugin::registerSchedules', [&$definitions]);
        }

        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $key = trim((string) ($definition['key'] ?? ''));
            $jobType = trim((string) ($definition['job_type'] ?? ''));
            $name = trim((string) ($definition['display_name'] ?? ''));
            $cron = trim((string) ($definition['cron'] ?? ''));
            if ($key === '' || $jobType === '' || $name === '' || !$this->isValidCron($cron)) {
                continue;
            }

            $contextId = (int) ($definition['context_id'] ?? 0);
            $timezone = trim((string) ($definition['timezone'] ?? date_default_timezone_get()));
            try {
                new \DateTimeZone($timezone);
            } catch (Throwable $e) {
                $timezone = date_default_timezone_get();
            }

            if (!$this->calculateNextRun($cron, $timezone)) {
                continue;
            }

            $existing = Capsule::table(self::TABLE)
                ->where('schedule_key', '=', $key)
                ->where('context_id', '=', $contextId)
                ->first();
            $values = [
                'schedule_key' => $key,
                'context_id' => $contextId,
                'display_name' => $name,
                'job_type' => $jobType,
                'cron_expression' => $cron,
                'timezone' => $timezone,
                'payload' => $this->encodeJson($definition['payload'] ?? []),
                'options' => $this->encodeJson($definition['options'] ?? []),
                'updated_at' => $this->now(),
            ];

            if (!$existing) {
                $values['enabled'] = array_key_exists('enabled', $definition) ? (bool) $definition['enabled'] : false;
                $values['next_run_at'] = $values['enabled'] ? $this->calculateNextRun($cron, $timezone) : null;
                $values['created_at'] = $this->now();
                Capsule::table(self::TABLE)->insert($values);
            } else {
                // Keep administrator changes to the cadence and timezone. The
                // plugin definition is the initial default, not a reset on
                // every page load or cron tick.
                Capsule::table(self::TABLE)
                    ->where('id', '=', (int) $existing->id)
                    ->update([
                        'display_name' => $values['display_name'],
                        'job_type' => $values['job_type'],
                        'payload' => $values['payload'],
                        'options' => $values['options'],
                        'updated_at' => $values['updated_at'],
                    ]);
            }
        }
    }

    protected function findSchedule($scheduleId)
    {
        return Capsule::table(self::TABLE)->where('id', '=', (int) $scheduleId)->first();
    }

    protected function canMutateSchedule($schedule, $contextId, $allowGlobal)
    {
        if ($allowGlobal) {
            return true;
        }

        $contextId = (int) $contextId;
        if ($contextId > 0) {
            return (int) $schedule->context_id === $contextId;
        }

        return (int) $schedule->context_id === 0;
    }

    protected function scheduleDispatchRetry($row, $claimedNextRun, $message)
    {
        $attempts = min(255, ((int) ($row->dispatch_attempts ?? 0)) + 1);
        $delay = min(
            self::DISPATCH_RETRY_MAX_SECONDS,
            self::DISPATCH_RETRY_BASE_SECONDS * (2 ** min(6, $attempts - 1))
        );
        $retryAt = gmdate('Y-m-d H:i:s', time() + $delay);

        Capsule::table(self::TABLE)
            ->where('id', '=', (int) $row->id)
            ->where('enabled', '=', 1)
            ->where('next_run_at', '=', $claimedNextRun)
            ->update([
                'next_run_at' => $retryAt,
                'dispatch_attempts' => $attempts,
                'last_status' => 'failed',
                'last_error' => substr((string) $message, 0, 4000),
                'updated_at' => $this->now(),
            ]);
    }

    protected function formatSchedule($row)
    {
        $historyCount = (int) Capsule::table(self::RUNS_TABLE)
            ->where('schedule_id', '=', (int) $row->id)
            ->count();
        $failedCount = (int) Capsule::table(self::RUNS_TABLE)
            ->where('schedule_id', '=', (int) $row->id)
            ->where('status', '=', 'failed')
            ->count();

        return [
            'id' => (int) $row->id,
            'key' => (string) $row->schedule_key,
            'name' => (string) $row->display_name,
            'jobType' => (string) $row->job_type,
            'cron' => (string) $row->cron_expression,
            'timezone' => (string) $row->timezone,
            'enabled' => (bool) $row->enabled,
            'human' => $this->humanizeCron($row->cron_expression),
            'next' => $row->enabled ? $this->formatScheduleTime($row->next_run_at, $row->timezone) : 'Paused',
            'last' => $this->formatScheduleTime($row->last_run_at, $row->timezone),
            'lastStatus' => $row->last_status ?: null,
            'lastStatusLabel' => $this->scheduleStatusLabel($row->last_status ?: ''),
            'lastJobId' => $row->last_job_id ?: null,
            'lastError' => $row->last_error ?: null,
            'historyCount' => $historyCount,
            'failedCount' => $failedCount,
            'contextId' => (int) $row->context_id,
        ];
    }

    protected function formatRun($row, $jobRun, $timezone)
    {
        // The schedule-run row represents the logical execution. WorkerBee
        // may mark an individual attempt as failed before retrying it, so the
        // schedule-run status remains authoritative here.
        $status = (string) $row->status;
        $details = $this->decodeJson($row->details);
        if ($jobRun && !empty($jobRun->details)) {
            $details['worker'] = $this->decodeJson($jobRun->details);
        }

        $started = $jobRun && $jobRun->started_at ? $jobRun->started_at : $row->started_at;
        $finished = $jobRun && $jobRun->finished_at ? $jobRun->finished_at : $row->finished_at;
        $stopped = $jobRun && $jobRun->stopped_at ? $jobRun->stopped_at : $row->stopped_at;
        // A WorkerBee attempt can have an old error after a retry. The
        // logical schedule-run status is authoritative, so completed and
        // currently-running retries must not surface that stale error.
        $error = $status === 'failed'
            ? ($jobRun && $jobRun->error_message ? $jobRun->error_message : $row->error_message)
            : null;

        return [
            'id' => (int) $row->id,
            'jobId' => $row->queue_job_id ? (int) $row->queue_job_id : null,
            'trigger' => (string) $row->trigger_type,
            'status' => $status,
            'statusLabel' => $this->runStatusLabel($status),
            'progress' => $jobRun ? (int) $jobRun->progress : (int) $row->progress,
            'attempts' => $jobRun ? (int) $jobRun->attempts : (int) $row->attempts,
            'queuedAt' => $this->formatScheduleTime($row->queued_at ?: $row->created_at, $timezone),
            'startedAt' => $this->formatScheduleTime($started, $timezone),
            'finishedAt' => $this->formatScheduleTime($finished, $timezone),
            'stoppedAt' => $this->formatScheduleTime($stopped, $timezone),
            'duration' => $this->formatDuration($jobRun && $jobRun->duration_seconds !== null ? $jobRun->duration_seconds : $row->duration_seconds),
            'error' => $error ? (string) $error : null,
            'failureCause' => $this->failureCause($details, $status),
            'details' => $details,
        ];
    }

    protected function failureCause(array $details, $status)
    {
        if ((string) $status !== 'failed') {
            return null;
        }

        $result = isset($details['result']) && is_array($details['result'])
            ? $details['result']
            : [];
        return [
            'panel_api' => 'Panel Mailcow connection',
            'panel_auth' => 'Panel Mailcow authorization',
            'local_database' => 'Local suppression database',
            'configuration' => 'Enveloper configuration',
        ][(string) ($result['stage'] ?? '')] ?? 'WorkerBee or schedule processing';
    }

    protected function createRun($row, $manual, array $payload, array $options)
    {
        return (int) Capsule::table(self::RUNS_TABLE)->insertGetId([
            'schedule_id' => (int) $row->id,
            'context_id' => (int) $row->context_id,
            'trigger_type' => $manual ? 'manual' : 'scheduled',
            'status' => 'dispatching',
            'details' => $this->encodeJson([
                'schedule' => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->display_name,
                ],
                'payload' => $payload,
                'options' => $options,
            ]),
            'queued_at' => $this->now(),
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    protected function markRunQueued($runId, $jobId)
    {
        Capsule::table(self::RUNS_TABLE)
            ->where('id', '=', (int) $runId)
            ->update([
                'queue_job_id' => (int) $jobId,
                'status' => 'queued',
                'updated_at' => $this->now(),
            ]);
    }

    protected function updateScheduleAfterRun($scheduleId, $status, $error = null)
    {
        Capsule::table(self::TABLE)
            ->where('id', '=', (int) $scheduleId)
            ->update([
                'last_status' => (string) $status,
                'last_error' => $error ? substr((string) $error, 0, 4000) : null,
                'updated_at' => $this->now(),
            ]);
    }

    protected function mergeRunDetails($current, $extra)
    {
        $details = $this->decodeJson($current);
        foreach ((array) $extra as $key => $value) {
            $details[$key] = $value;
        }
        return $this->encodeJson($details);
    }

    protected function canViewSchedule($schedule, $contextId)
    {
        return (int) $contextId <= 0
            || (int) $schedule->context_id === 0
            || (int) $schedule->context_id === (int) $contextId;
    }

    protected function runStatusLabel($status)
    {
        return [
            'dispatching' => 'Starting',
            'queued' => 'Queued',
            'processing' => 'Running',
            'paused' => 'Paused',
            'done' => 'Completed',
            'failed' => 'Failed',
        ][(string) $status] ?? ucfirst((string) $status);
    }

    protected function scheduleStatusLabel($status)
    {
        return [
            'dispatching' => 'Starting',
            'manual_queued' => 'Queued',
            'queued' => 'Queued',
            'processing' => 'Running',
            'done' => 'Last run completed',
            'failed' => 'Last run failed',
            'paused' => 'Paused',
        ][(string) $status] ?? '';
    }

    protected function durationSeconds($started, $finished)
    {
        if (!$started || !$finished) {
            return null;
        }
        try {
            return max(0, (new \DateTime($finished))->getTimestamp() - (new \DateTime($started))->getTimestamp());
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function formatDuration($seconds)
    {
        if ($seconds === null || $seconds === '') {
            return '—';
        }
        $seconds = max(0, (int) $seconds);
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;
        return $minutes . 'm' . ($remaining ? ' ' . $remaining . 's' : '');
    }

    protected function ensureSchema()
    {
        MigrationManager::make($this->plugin)->runMigrations();

        $schema = Capsule::schema();
        if ($schema->hasTable(self::TABLE)
            && !$schema->hasColumn(self::TABLE, 'dispatch_attempts')) {
            $schema->table(self::TABLE, function ($table) {
                $table->unsignedTinyInteger('dispatch_attempts')->default(0)->after('last_run_at');
            });
        }
    }

    protected function encodeJson($value)
    {
        $encoded = json_encode(is_array($value) ? $value : [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $encoded === false ? '{}' : $encoded;
    }

    protected function decodeJson($value)
    {
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function now()
    {
        return gmdate('Y-m-d H:i:s');
    }

    protected function formatScheduleTime($value, $timezone)
    {
        if (!$value) {
            return '—';
        }

        try {
            $date = new \DateTime((string) $value, new \DateTimeZone('UTC'));
            $date->setTimezone(new \DateTimeZone($timezone ?: date_default_timezone_get()));
            return $date->format('d M Y H:i');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    protected function isValidCron($expression)
    {
        $expression = trim((string) $expression);
        if ($expression === '' || strlen($expression) > self::MAX_CRON_LENGTH) {
            return false;
        }

        $parts = preg_split('/\s+/', $expression);
        if (count($parts) !== 5) {
            return false;
        }

        $termCount = 0;
        foreach ($parts as $part) {
            $termCount += substr_count($part, ',') + 1;
        }
        if ($termCount > self::MAX_CRON_TERMS) {
            return false;
        }

        $fields = [];
        foreach ([[0, 59], [0, 23], [1, 31], [1, 12], [0, 7]] as $index => $range) {
            $fields[$index] = $this->parseCronField($parts[$index], $range[0], $range[1]);
            if (empty($fields[$index])) {
                return false;
            }
        }

        return $this->hasPossibleCalendarDate($parts, $fields);
    }

    protected function hasPossibleCalendarDate(array $parts, array $fields)
    {
        // When weekday is restricted, cron's day-of-month/day-of-week rule
        // uses OR semantics, so a valid weekday is enough to make the pattern
        // reachable. The impossible case is a restricted month/day pair with
        // no weekday fallback, such as 31 February.
        if (trim($parts[2]) === '*' || trim($parts[3]) === '*' || trim($parts[4]) !== '*') {
            return true;
        }

        $monthLengths = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        foreach ($fields[3] as $month) {
            $maxDay = $monthLengths[$month - 1] ?? 0;
            foreach ($fields[2] as $day) {
                if ($day <= $maxDay) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function calculateNextRun($expression, $timezone, $from = null)
    {
        $parts = preg_split('/\s+/', trim((string) $expression));
        if (count($parts) !== 5) {
            return null;
        }

        $fields = [
            $this->parseCronField($parts[0], 0, 59),
            $this->parseCronField($parts[1], 0, 23),
            $this->parseCronField($parts[2], 1, 31),
            $this->parseCronField($parts[3], 1, 12),
            $this->parseCronField($parts[4], 0, 7),
        ];
        if (in_array([], $fields, true)) {
            return null;
        }

        try {
            $zone = new \DateTimeZone($timezone ?: date_default_timezone_get());
        } catch (Throwable $e) {
            $zone = new \DateTimeZone(date_default_timezone_get());
        }

        $date = $from instanceof \DateTimeInterface
            ? new \DateTime($from->format('Y-m-d H:i:00'), $zone)
            : new \DateTime('now', $zone);
        $date->setTime((int) $date->format('H'), (int) $date->format('i'), 0);
        $date->modify('+1 minute');

        sort($fields[0]);
        sort($fields[1]);
        $day = new \DateTime($date->format('Y-m-d 00:00:00'), $zone);
        for ($i = 0; $i < self::MAX_CRON_SEARCH_DAYS; $i++) {
            $dayOfMonthMatches = in_array((int) $day->format('j'), $fields[2], true);
            $dayOfWeekMatches = in_array((int) $day->format('w'), $fields[4], true)
                || ((int) $day->format('w') === 0 && in_array(7, $fields[4], true));
            $domAny = trim($parts[2]) === '*';
            $dowAny = trim($parts[4]) === '*';
            $dayMatches = ($domAny && $dowAny)
                ? true
                : ($domAny ? $dayOfWeekMatches : ($dowAny ? $dayOfMonthMatches : ($dayOfMonthMatches || $dayOfWeekMatches)));

            if ($dayMatches && in_array((int) $day->format('n'), $fields[3], true)) {
                foreach ($fields[1] as $hour) {
                    foreach ($fields[0] as $minute) {
                        $candidate = new \DateTime(
                            $day->format('Y-m-d') . sprintf(' %02d:%02d:00', $hour, $minute),
                            $zone
                        );
                        if ($candidate <= $date) {
                            continue;
                        }
                        if ((int) $candidate->format('G') !== $hour || (int) $candidate->format('i') !== $minute) {
                            continue;
                        }
                        return $candidate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    }
                }
            }
            $day->modify('+1 day')->setTime(0, 0, 0);
        }

        return null;
    }

    protected function parseCronField($field, $min, $max)
    {
        $values = [];
        foreach (explode(',', trim((string) $field)) as $part) {
            $part = trim($part);
            if ($part === '') {
                return [];
            }

            $step = 1;
            if (strpos($part, '/') !== false) {
                [$part, $stepValue] = explode('/', $part, 2);
                $step = (int) $stepValue;
                if ($step < 1) {
                    return [];
                }
            }

            if ($part === '*') {
                $start = $min;
                $end = $max;
            } elseif (strpos($part, '-') !== false) {
                [$start, $end] = array_map('intval', explode('-', $part, 2));
            } elseif (is_numeric($part)) {
                $start = (int) $part;
                $end = $start;
            } else {
                return [];
            }

            if ($start < $min || $end > $max || $start > $end) {
                return [];
            }
            for ($value = $start; $value <= $end; $value += $step) {
                $values[$value] = true;
            }
        }

        return array_map('intval', array_keys($values));
    }

    protected function humanizeCron($expression)
    {
        $parts = preg_split('/\s+/', trim((string) $expression));
        if (count($parts) !== 5) {
            return 'Custom schedule';
        }

        $time = function ($minute, $hour) {
            return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':'
                . str_pad($minute, 2, '0', STR_PAD_LEFT);
        };

        if (preg_match('/^\d+$/', $parts[0]) && preg_match('/^\d+$/', $parts[1])
            && $parts[2] === '*' && $parts[3] === '*' && $parts[4] === '*') {
            return 'Every day at ' . $time($parts[0], $parts[1]);
        }
        if ($parts[0] === '0' && preg_match('/^\*\/\d+$/', $parts[1]) && $parts[2] === '*' && $parts[3] === '*' && $parts[4] === '*') {
            $hours = (int) substr($parts[1], 2);
            return $hours === 1 ? 'Every hour' : 'Every ' . $hours . ' hours';
        }
        if (preg_match('/^\*\/\d+$/', $parts[0]) && $parts[1] === '*' && $parts[2] === '*' && $parts[3] === '*' && $parts[4] === '*') {
            return 'Every ' . substr($parts[0], 2) . ' minutes';
        }

        if (preg_match('/^\d+$/', $parts[0]) && preg_match('/^\d+$/', $parts[1])
            && $parts[2] === '*' && $parts[3] === '*' && preg_match('/^[0-7]$/', $parts[4])) {
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $day = $days[(int) $parts[4] === 7 ? 0 : (int) $parts[4]];
            return 'Every ' . $day . ' at ' . $time($parts[0], $parts[1]);
        }

        if (preg_match('/^\d+$/', $parts[0]) && preg_match('/^\d+$/', $parts[1])
            && $parts[2] === '1' && $parts[3] === '*' && $parts[4] === '*') {
            return 'On the 1st of every month at ' . $time($parts[0], $parts[1]);
        }

        return 'Custom schedule';
    }
}
