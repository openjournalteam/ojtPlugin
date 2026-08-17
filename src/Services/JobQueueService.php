<?php

namespace Openjournalteam\OjtPlugin\Services;

use Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Openjournalteam\OjtPlugin\Migrations\MigrationManager;
use Registry;
use Throwable;

class JobQueueService
{
    const CONNECTION = 'ojtPluginPersistent';
    const JOBS_TABLE = 'ojt_jobs';
    const FAILED_JOBS_TABLE = 'ojt_failed_jobs';
    const JOB_RUNS_TABLE = 'ojt_job_tracking';
    const QUEUE_RETRY_AFTER_SECONDS = 900;
    const WORKER_TIMEOUT_BUFFER_SECONDS = 60;

    /** @var bool */
    protected static $queueConnectionBooted = false;

    /** @var \OjtPlugin */
    protected $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Dispatch one job into Laravel database queue (connection: persistent).
     *
     * @param string $jobType
     * @param array $payload
     * @param array $options queue, delaySeconds, contextId
     * @return mixed Job ID from queue backend.
     */
    public function dispatch($jobType, $payload = [], $options = [])
    {
        if (!$jobType) {
            return null;
        }
        if (method_exists($this->plugin, 'areBackgroundJobsEnabled')
            && !$this->plugin->areBackgroundJobsEnabled()) {
            error_log('OjtWorkerBee dispatch skipped: background jobs are disabled.');
            return null;
        }

        $this->bootQueueConnection();

        $queueName = (string) ($options['queue'] ?? 'default');
        $delaySeconds = (int) ($options['delaySeconds'] ?? 0);
        $contextId = $this->resolveContextId($options, $payload);
        $trackingToken = bin2hex(random_bytes(16));
        $data = [
            'jobType' => (string) $jobType,
            'payload' => is_array($payload) ? $payload : [],
            'contextId' => $contextId ?: null,
            'maxAttempts' => isset($options['maxAttempts']) ? max(1, (int) $options['maxAttempts']) : null,
            'enqueuedAt' => time(),
            'trackingToken' => $trackingToken,
        ];

        $job = 'Openjournalteam\\OjtPlugin\\Jobs\\Handlers\\JobHandler@fire';
        $this->recordDispatched(null, $jobType, $queueName, $contextId, $options, time() + $delaySeconds, $trackingToken, $payload);
        try {
            if ($delaySeconds > 0) {
                $jobId = Queue::later($delaySeconds, $job, $data, $queueName, self::CONNECTION);
                $this->persistContextId($jobId, $contextId);
                $this->bindTrackingToken($trackingToken, $jobId);
                return $jobId;
            }

            $jobId = Queue::push($job, $data, $queueName, self::CONNECTION);
            $this->persistContextId($jobId, $contextId);
            $this->bindTrackingToken($trackingToken, $jobId);
            return $jobId;
        } catch (Throwable $e) {
            $this->deleteTrackingToken($trackingToken);
            error_log('OjtWorkerBee dispatch failed: ' . $e->getMessage());
            return null;
        }
    }

    public function isEnabledForRuntime()
    {
        $plugin = $this->plugin;

        if ($plugin->getEnabled()) {
            return true;
        }

        $pluginName = strtolower_codesafe($plugin->getName());
        $pluginSettingsDao = \DAORegistry::getDAO('PluginSettingsDAO');
        $result = $pluginSettingsDao->retrieve(
            'SELECT setting_value FROM plugin_settings WHERE plugin_name = ? AND setting_name = ? AND setting_value IN (?, ?, ?, ?) LIMIT 1',
            [
                $pluginName,
                'enabled',
                '1',
                'true',
                'on',
                'yes',
            ]
        );

        return (bool) $result->current();
    }

    /**
     * Run exactly one queued job.
     *
     * @param string $queueName
     * @param array $options sleep, tries, timeout
     * @return int
     */
    public function runNext($queueName = 'default', $options = [])
    {
        if (method_exists($this->plugin, 'areBackgroundJobsEnabled')
            && !$this->plugin->areBackgroundJobsEnabled()) {
            return 0;
        }
        $this->bootQueueConnection();
        $worker = $this->makeWorker();
        $workerOptions = $this->makeWorkerOptions($options, false);
        $worker->runNextJob(self::CONNECTION, $queueName, $workerOptions);
        return 1;
    }

    /**
     * Run a daemon worker loop.
     *
     * @param string $queueName
     * @param array $options sleep, tries, timeout, stopWhenEmpty
     * @return void
     */
    public function daemon($queueName = 'default', $options = [])
    {
        $this->bootQueueConnection();
        $worker = $this->makeWorker();
        $workerOptions = $this->makeWorkerOptions($options, (bool) ($options['stopWhenEmpty'] ?? false));
        $worker->daemon(self::CONNECTION, $queueName, $workerOptions);
    }

    /**
     * Get queue stats from jobs table.
     */
    public function stats($queueName = 'default')
    {
        MigrationManager::make($this->plugin)->runMigrations();
        $query = Capsule::table(self::JOB_RUNS_TABLE)->where('queue', '=', (string) $queueName);

        return [
            'pending' => (int) (clone $query)->where('status', '=', 'queued')->count(),
            'running' => (int) (clone $query)->whereIn('status', ['processing', 'paused'])->count(),
            'total' => (int) (clone $query)->count(),
        ];
    }

    /**
     * Return paginated, journal-scoped job records for the control panel.
     *
     * @param array $options page, pageSize, filter, query, dateRange, contextId, queue
     * @return array
     */
    public function listJobs(array $options = [])
    {
        $this->ensureRuntimeTables();

        $page = max(1, (int) ($options['page'] ?? 1));
        $pageSize = min(50, max(1, (int) ($options['pageSize'] ?? 10)));
        $filter = (string) ($options['filter'] ?? 'all');
        $search = trim((string) ($options['query'] ?? ''));
        $dateRange = (string) ($options['dateRange'] ?? 'all');
        $contextId = (int) ($options['contextId'] ?? 0);
        $queueName = (string) ($options['queue'] ?? 'default');

        // Make jobs created before the tracking migration visible as well.
        $this->syncUntrackedQueueRows($contextId, $queueName);
        $this->syncUntrackedFailedRows($contextId, $queueName);
        $this->syncMissingJobDetails($contextId, $queueName);

        $query = Capsule::table(self::JOB_RUNS_TABLE)
            ->where('queue', '=', $queueName)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($contextId > 0) {
            $query->where('context_id', '=', $contextId);
        }

        $this->applyDateRange($query, $dateRange);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('display_name', 'like', '%' . $search . '%')
                    ->orWhere('job_type', 'like', '%' . $search . '%');
            });
        }

        if ($filter === 'active') {
            $query->whereIn('status', ['queued', 'processing', 'paused']);
        } elseif ($filter === 'done') {
            $query->where('status', '=', 'done');
        } elseif ($filter === 'failed') {
            $query->where('status', '=', 'failed');
        }

        $total = (int) (clone $query)->count();
        $rows = $query->forPage($page, $pageSize)->get();

        return [
            'jobs' => array_map(function ($row) {
                return $this->serializeJobRun($row);
            }, $rows->all()),
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => max(1, (int) ceil($total / $pageSize)),
            ],
            'counts' => $this->jobCounts($contextId, $queueName, $dateRange),
        ];
    }

    /**
     * Get status counts scoped to one journal.
     */
    public function jobCounts($contextId = 0, $queueName = 'default', $dateRange = 'all')
    {
        $this->ensureRuntimeTables();
        $query = Capsule::table(self::JOB_RUNS_TABLE)
            ->where('queue', '=', (string) $queueName);

        if ((int) $contextId > 0) {
            $query->where('context_id', '=', (int) $contextId);
        }

        $this->applyDateRange($query, $dateRange);

        $rows = $query->select('status', Capsule::raw('COUNT(*) AS count'))
            ->groupBy('status')
            ->get();
        $counts = ['all' => 0, 'active' => 0, 'done' => 0, 'failed' => 0];

        foreach ($rows as $row) {
            $count = (int) $row->count;
            $counts['all'] += $count;
            if (in_array($row->status, ['queued', 'processing', 'paused'], true)) {
                $counts['active'] += $count;
            } elseif ($row->status === 'done') {
                $counts['done'] += $count;
            } elseif ($row->status === 'failed') {
                $counts['failed'] += $count;
            }
        }

        return $counts;
    }

    /**
     * Apply the date filter used by the Background Jobs list and counters.
     * Records are grouped by when the tracking record was created/queued.
     */
    protected function applyDateRange($query, $dateRange)
    {
        $dateRange = (string) $dateRange;
        $start = null;

        switch ($dateRange) {
            case 'today':
                $start = date('Y-m-d 00:00:00');
                break;
            case '7d':
                $start = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30d':
                $start = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case 'month':
                $start = date('Y-m-01 00:00:00');
                break;
        }

        if ($start !== null) {
            $query->where('created_at', '>=', $start);
        }

        return $query;
    }

    /**
     * Pause a queued job. A reserved job cannot be safely suspended by a web request.
     *
     * @return array
     */
    public function pause($runId, $contextId = 0)
    {
        $run = $this->findRun($runId, $contextId);
        if (!$run) {
            return ['success' => false, 'message' => 'Job not found.'];
        }

        if ($run->status !== 'queued') {
            return ['success' => false, 'message' => 'Only queued jobs can be paused safely.'];
        }

        $queueJob = $this->findQueueJob($run->queue_job_id);
        if (!$queueJob || $queueJob->reserved_at !== null) {
            return ['success' => false, 'message' => 'This job has already started processing.'];
        }

        $now = time();
        Capsule::table(self::JOBS_TABLE)->where('id', '=', (int) $run->queue_job_id)->update([
            'available_at' => $now + (10 * 365 * 24 * 60 * 60),
        ]);
        Capsule::table(self::JOB_RUNS_TABLE)->where('id', '=', (int) $run->id)->update([
            'status' => 'paused',
            'paused_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        return ['success' => true, 'message' => 'Job paused.'];
    }

    /**
     * Resume a job that was paused before the worker reserved it.
     */
    public function resume($runId, $contextId = 0)
    {
        $run = $this->findRun($runId, $contextId);
        if (!$run) {
            return ['success' => false, 'message' => 'Job not found.'];
        }

        if ($run->status !== 'paused') {
            return ['success' => false, 'message' => 'Only paused jobs can be resumed.'];
        }

        if (!$this->findQueueJob($run->queue_job_id)) {
            return ['success' => false, 'message' => 'The queue record is no longer available.'];
        }

        Capsule::table(self::JOBS_TABLE)->where('id', '=', (int) $run->queue_job_id)->update([
            'available_at' => time(),
            'reserved_at' => null,
        ]);
        Capsule::table(self::JOB_RUNS_TABLE)->where('id', '=', (int) $run->id)->update([
            'status' => 'queued',
            'paused_at' => null,
            'updated_at' => $this->now(),
        ]);

        return ['success' => true, 'message' => 'Job resumed.'];
    }

    /**
     * Stop one job. Queued jobs can be removed immediately; reserved jobs are
     * marked for cooperative stop and are finalized by the worker event.
     */
    public function stop($runId, $contextId = 0)
    {
        $run = $this->findRun($runId, $contextId);
        if (!$run) {
            return ['success' => false, 'message' => 'Job not found.'];
        }

        if (!in_array($run->status, ['queued', 'paused', 'processing'], true)) {
            return ['success' => false, 'message' => 'This job is no longer active.'];
        }

        $queueJob = $this->findQueueJob($run->queue_job_id);
        if ($queueJob && $queueJob->reserved_at === null) {
            Capsule::table(self::JOBS_TABLE)->where('id', '=', (int) $run->queue_job_id)->delete();
            $this->markFailed($run->queue_job_id, 'Stopped by user.', null, true);
            return ['success' => true, 'message' => 'Job stopped.'];
        }

        Capsule::table(self::JOB_RUNS_TABLE)->where('id', '=', (int) $run->id)->update([
            'cancel_requested_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        return ['success' => true, 'message' => 'Stop requested. The worker will finalize this job shortly.'];
    }

    /**
     * Stop every active job in the current journal.
     */
    public function stopAll($contextId = 0, $queueName = 'default')
    {
        $this->ensureRuntimeTables();
        $query = Capsule::table(self::JOB_RUNS_TABLE)
            ->where('queue', '=', (string) $queueName)
            ->whereIn('status', ['queued', 'paused', 'processing']);
        if ((int) $contextId > 0) {
            $query->where('context_id', '=', (int) $contextId);
        }

        $runs = $query->get();
        $stopped = 0;
        foreach ($runs as $run) {
            $result = $this->stop($run->id, $contextId);
            if (!empty($result['success'])) {
                $stopped++;
            }
        }

        return ['success' => true, 'message' => $stopped . ' job(s) stopped or marked for stopping.', 'count' => $stopped];
    }

    /**
     * Requeue a failed job using its original Laravel payload.
     */
    public function retry($runId, $contextId = 0)
    {
        if (method_exists($this->plugin, 'areBackgroundJobsEnabled')
            && !$this->plugin->areBackgroundJobsEnabled()) {
            return ['success' => false, 'message' => 'Background jobs are disabled.'];
        }
        $this->ensureRuntimeTables();
        $this->ensureRetryClaimColumn();
        $run = $this->findRun($runId, $contextId);
        if (!$run || $run->status !== 'failed') {
            return ['success' => false, 'message' => 'Only failed jobs can be retried.'];
        }

        $failedQuery = Capsule::table(self::FAILED_JOBS_TABLE);
        if (!empty($run->failed_job_id)) {
            $failedQuery->where('id', '=', (int) $run->failed_job_id);
        } else {
            $failedQuery->where('queue_job_id', '=', (int) $run->queue_job_id);
        }
        $failed = $failedQuery->orderBy('id', 'desc')->first();
        if (!$failed) {
            return ['success' => false, 'message' => 'The failed job payload is no longer available.'];
        }

        $payload = json_decode((string) $failed->payload, true);
        $jobName = isset($payload['job']) ? (string) $payload['job'] : '';
        $data = $this->extractQueueData($payload);
        if ($jobName === '' || !is_array($data)) {
            return ['success' => false, 'message' => 'The failed job payload is invalid.'];
        }

        $retryClaimedAt = $this->now();
        $retryClaimCutoff = date('Y-m-d H:i:s', time() - self::QUEUE_RETRY_AFTER_SECONDS);
        $trackingToken = null;
        try {
            $jobId = Capsule::connection()->transaction(function () use (
                $failed,
                $retryClaimedAt,
                $retryClaimCutoff,
                $run,
                $jobName,
                $data,
                &$trackingToken
            ) {
                $claimed = Capsule::table(self::FAILED_JOBS_TABLE)
                    ->where('id', '=', (int) $failed->id)
                    ->where(function ($query) use ($retryClaimCutoff) {
                        $query->whereNull('retry_claimed_at')
                            ->orWhere('retry_claimed_at', '<=', $retryClaimCutoff);
                    })
                    ->update(['retry_claimed_at' => $retryClaimedAt]);
                if (!$claimed) {
                    return null;
                }

                $this->bootQueueConnection();
                $trackingToken = bin2hex(random_bytes(16));
                $data['trackingToken'] = $trackingToken;
                $this->recordDispatched(null, $run->job_type, $run->queue, $run->context_id, [
                    'displayName' => $run->display_name,
                    'maxAttempts' => $run->max_attempts,
                ], time(), $trackingToken);
                $jobId = Queue::push($jobName, $data, (string) $run->queue, self::CONNECTION);
                if (!$jobId) {
                    throw new \RuntimeException('Queue backend did not return a job id.');
                }

                $this->bindTrackingToken($trackingToken, $jobId);
                $deleted = Capsule::table(self::FAILED_JOBS_TABLE)
                    ->where('id', '=', (int) $failed->id)
                    ->where('retry_claimed_at', '=', $retryClaimedAt)
                    ->delete();
                if (!$deleted) {
                    throw new \RuntimeException('Failed-job record changed during retry.');
                }

                return $jobId;
            });
            if (!$jobId) {
                return ['success' => false, 'message' => 'This failed job is already being retried.'];
            }

            return ['success' => true, 'message' => 'Job added back to the queue.', 'jobId' => $jobId];
        } catch (Throwable $e) {
            if (!empty($trackingToken)) {
                $this->deleteTrackingToken($trackingToken);
            }
            return ['success' => false, 'message' => 'Unable to retry this job.'];
        }
    }

    /**
     * Mark a worker job as processing.
     */
    public function markProcessing($queueJobId, $attempts = null, $trackingToken = null)
    {
        $run = $this->findRunByQueueId($queueJobId, $trackingToken);
        if (!$run) {
            return;
        }

        $values = [
            'status' => 'processing',
            'started_at' => $run->started_at ?: $this->now(),
            'updated_at' => $this->now(),
        ];
        if ($attempts !== null) {
            $values['attempts'] = max(0, (int) $attempts);
        }
        Capsule::table(self::JOB_RUNS_TABLE)->where('id', '=', (int) $run->id)->update($values);
    }

    /**
     * Mark a worker job as completed, unless a stop was requested.
     */
    public function markCompleted($queueJobId, $attempts = null, $trackingToken = null)
    {
        $run = $this->findRunByQueueId($queueJobId, $trackingToken);
        if (!$run) {
            return;
        }

        if ($run->cancel_requested_at) {
            $this->markFailed($queueJobId, 'Stopped by user.', null, true);
            return;
        }

        $finishedAt = $this->now();
        $values = [
            'status' => 'done',
            'progress' => 100,
            'finished_at' => $finishedAt,
            'duration_seconds' => $this->durationSeconds($run->started_at, $finishedAt),
            'updated_at' => $finishedAt,
        ];
        if ($attempts !== null) {
            $values['attempts'] = max(0, (int) $attempts);
        }
        Capsule::table(self::JOB_RUNS_TABLE)->where('id', '=', (int) $run->id)->update($values);
    }

    /**
     * Mark a worker job as failed or stopped.
     */
    public function markFailed($queueJobId, $message = null, $failedJobId = null, $stopped = false, $trackingToken = null)
    {
        $run = $this->findRunByQueueId($queueJobId, $trackingToken);
        if (!$run) {
            return;
        }

        $stoppedAt = $this->now();
        $values = [
            'status' => 'failed',
            'stopped_at' => $stoppedAt,
            'duration_seconds' => $this->durationSeconds($run->started_at, $stoppedAt),
            'error_message' => $this->safeErrorMessage($message ?: ($stopped ? 'Stopped by user.' : 'Job failed.')),
            'updated_at' => $stoppedAt,
        ];
        if ($failedJobId !== null) {
            $values['failed_job_id'] = (int) $failedJobId;
        }
        Capsule::table(self::JOB_RUNS_TABLE)->where('id', '=', (int) $run->id)->update($values);
    }

    /**
     * Store progress from a cooperative job handler.
     */
    public function updateProgress($queueJobId, $progress, $trackingToken = null)
    {
        $run = $this->findRunByQueueId($queueJobId, $trackingToken);
        if (!$run || in_array($run->status, ['done', 'failed'], true)) {
            return;
        }

        Capsule::table(self::JOB_RUNS_TABLE)->where('id', '=', (int) $run->id)->update([
            'progress' => min(100, max(0, (int) $progress)),
            'updated_at' => $this->now(),
        ]);
    }

    /**
     * Check whether a worker should stop the current job.
     */
    public function isCancellationRequested($queueJobId, $trackingToken = null)
    {
        $run = $this->findRunByQueueId($queueJobId, $trackingToken);
        return $run && $run->cancel_requested_at !== null;
    }

    /**
     * Insert the UI tracking row after Laravel inserts the queue row.
     */
    protected function recordDispatched($jobId, $jobType, $queueName, $contextId, array $options, $availableAt, $trackingToken = null, array $payload = [])
    {
        if (!$jobId && !$trackingToken) {
            return;
        }

        try {
            $this->ensureRuntimeTables();
            $now = $this->now();
            Capsule::table(self::JOB_RUNS_TABLE)->insert([
                'queue_job_id' => $jobId ? (int) $jobId : null,
                'context_id' => $contextId ? (int) $contextId : null,
                'tracking_token' => $trackingToken,
                'queue' => (string) $queueName,
                'job_type' => (string) $jobType,
                'display_name' => $this->displayName($jobType, $options),
                'details' => $this->encodeJobDetails($payload, $options),
                'status' => 'queued',
                'progress' => 0,
                'attempts' => 0,
                'max_attempts' => isset($options['maxAttempts']) ? max(1, (int) $options['maxAttempts']) : null,
                'available_at' => (int) $availableAt,
                'queued_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            error_log('OjtWorkerBee job tracking insert failed: ' . $e->getMessage());
        }
    }

    protected function bindTrackingToken($trackingToken, $jobId)
    {
        if (!$trackingToken || !$jobId) {
            return;
        }

        Capsule::table(self::JOB_RUNS_TABLE)
            ->where('tracking_token', '=', (string) $trackingToken)
            ->update([
                'queue_job_id' => (int) $jobId,
                'updated_at' => $this->now(),
            ]);
    }

    protected function deleteTrackingToken($trackingToken)
    {
        if (!$trackingToken) {
            return;
        }

        Capsule::table(self::JOB_RUNS_TABLE)
            ->where('tracking_token', '=', (string) $trackingToken)
            ->delete();
    }

    protected function findRun($runId, $contextId = 0)
    {
        $query = Capsule::table(self::JOB_RUNS_TABLE)->where('id', '=', (int) $runId);
        if ((int) $contextId > 0) {
            $query->where('context_id', '=', (int) $contextId);
        }
        return $query->first();
    }

    protected function findRunByQueueId($queueJobId, $trackingToken = null)
    {
        if ($queueJobId) {
            $run = Capsule::table(self::JOB_RUNS_TABLE)
                ->where('queue_job_id', '=', (int) $queueJobId)
                ->orderBy('id', 'desc')
                ->first();
            if ($run) {
                return $run;
            }
        }

        if ($trackingToken) {
            return Capsule::table(self::JOB_RUNS_TABLE)
                ->where('tracking_token', '=', (string) $trackingToken)
                ->orderBy('id', 'desc')
                ->first();
        }

        return null;
    }

    protected function findQueueJob($queueJobId)
    {
        if (!$queueJobId) {
            return null;
        }
        return Capsule::table(self::JOBS_TABLE)->where('id', '=', (int) $queueJobId)->first();
    }

    protected function serializeJobRun($row)
    {
        $status = (string) $row->status;
        $progress = min(100, max(0, (int) $row->progress));
        $meta = $status === 'processing' ? $progress . '%' : null;
        if ($status === 'paused') {
            $meta = 'Paused';
        } elseif ($status === 'queued') {
            $meta = 'Waiting...';
        } elseif ($status === 'done') {
            $meta = 'Completed';
        } elseif ($status === 'failed') {
            $meta = $progress > 0 ? 'Failed at ' . $progress . '%' : 'Failed';
        }
        $details = $this->decodeJobDetails($row->details);
        $email = $this->resolveEmailDetails((string) $row->job_type, $details);
        if (is_array($email)) {
            $details['email'] = $email;
        }

        return [
            'id' => (int) $row->id,
            'name' => (string) $row->display_name,
            'type' => (string) $row->job_type,
            'queue' => (string) $row->queue,
            'status' => $status,
            'progress' => $progress,
            'meta' => $meta,
            'queuedAt' => $this->formatTime($row->queued_at) ?: $this->formatTime($row->created_at),
            'started' => $this->formatTime($row->started_at) ?: '—',
            'pausedAt' => $this->formatTime($row->paused_at),
            'finished' => $this->formatTime($row->finished_at),
            'stopped' => $this->formatTime($row->stopped_at),
            'duration' => $this->formatDuration($row->duration_seconds),
            'attempts' => (int) $row->attempts,
            'maxAttempts' => $row->max_attempts !== null ? (int) $row->max_attempts : null,
            'cancelRequested' => $row->cancel_requested_at !== null,
            'error' => $row->error_message ? (string) $row->error_message : null,
            'details' => $details,
            'createdAt' => $this->formatDateTime($row->created_at),
        ];
    }

    /**
     * Resolve the full mail envelope for Enveloper jobs.
     *
     * WorkerBee intentionally stores only the Enveloper queue id in its job
     * payload. The message itself remains in Enveloper's queue table, so the
     * admin details view can show useful email information without copying the
     * entire mail body into the generic WorkerBee tracking row.
     */
    protected function resolveEmailDetails($jobType, array $details)
    {
        if ((string) $jobType !== 'enveloper.sendQueuedMail') {
            return null;
        }

        $jobPayload = isset($details['payload']) && is_array($details['payload'])
            ? $details['payload']
            : [];
        $queueId = isset($jobPayload['queueId']) ? (int) $jobPayload['queueId'] : 0;
        if ($queueId <= 0) {
            return null;
        }

        $empty = [
            'available' => false,
            'queueId' => $queueId,
            'message' => 'The Enveloper mail queue record is no longer available.',
        ];

        try {
            $table = 'ojt_api_mailer_queue';
            if (!Capsule::schema()->hasTable($table)) {
                return $empty;
            }

            $row = Capsule::table($table)
                ->where('queue_id', '=', $queueId)
                ->first();
            if (!$row) {
                return $empty;
            }

            $mail = json_decode((string) $row->payload, true);
            if (!is_array($mail)) {
                return array_merge($empty, [
                    'message' => 'The Enveloper mail payload is not valid JSON.',
                ]);
            }

            $messageIds = json_decode((string) ($row->api_message_ids ?: '[]'), true);
            if (!is_array($messageIds)) {
                $messageIds = [];
            }

            $attachments = [];
            foreach ((array) ($mail['attachments'] ?? []) as $attachment) {
                if (!is_array($attachment)) {
                    continue;
                }

                $encodedContent = (string) ($attachment['content'] ?? '');
                $decodedContent = $encodedContent !== ''
                    ? base64_decode($encodedContent, true)
                    : false;
                $attachments[] = [
                    'name' => (string) ($attachment['filename'] ?? 'Attachment'),
                    'contentType' => (string) ($attachment['content_type'] ?? 'application/octet-stream'),
                    'size' => $decodedContent !== false
                        ? strlen($decodedContent)
                        : ($encodedContent !== '' ? strlen($encodedContent) : 0),
                ];
            }

            return [
                'available' => true,
                'queueId' => $queueId,
                'status' => (string) ($row->status ?? ''),
                'provider' => (string) ($mail['delivery_provider'] ?? ''),
                'from' => $this->formatEmailAddressList($mail['from'] ?? []),
                'replyTo' => $this->formatEmailAddressList($mail['reply_to'] ?? []),
                'to' => $this->formatEmailAddressList($mail['to'] ?? []),
                'cc' => $this->formatEmailAddressList($mail['cc'] ?? []),
                'bcc' => $this->formatEmailAddressList($mail['bcc'] ?? []),
                'subject' => (string) ($mail['subject'] ?? ''),
                'text' => (string) ($mail['text'] ?? ''),
                'html' => (string) ($mail['html'] ?? ''),
                'contentType' => (string) ($mail['content_type'] ?? ''),
                'headers' => $this->sanitizeJobDetails($mail['headers'] ?? []),
                'meta' => $this->sanitizeJobDetails($mail['meta'] ?? []),
                'attachments' => $attachments,
                'attempts' => (int) ($row->attempts ?? 0) + (int) ($row->manual_attempts ?? 0),
                'httpStatus' => $row->last_http_status ? (int) $row->last_http_status : null,
                'lastError' => $row->last_error ? (string) $row->last_error : null,
                'messageIds' => array_values(array_map('strval', $messageIds)),
                'queuedAt' => $this->formatDateTime($row->created_at),
                'updatedAt' => $this->formatDateTime($row->updated_at),
                'sentAt' => $this->formatDateTime($row->sent_at),
                'failedAt' => $this->formatDateTime($row->failed_at),
            ];
        } catch (Throwable $e) {
            error_log('OjtWorkerBee email details lookup failed: ' . $e->getMessage());
            return array_merge($empty, [
                'message' => 'Unable to read the Enveloper mail queue record.',
            ]);
        }
    }

    protected function formatEmailAddressList($addresses)
    {
        if (is_array($addresses)
            && (array_key_exists('email', $addresses) || array_key_exists('name', $addresses))) {
            $addresses = [$addresses];
        }

        $formatted = [];
        foreach ((array) $addresses as $address) {
            if (is_array($address)) {
                $email = trim((string) ($address['email'] ?? ''));
                $name = trim((string) ($address['name'] ?? ''));
                if ($email === '') {
                    continue;
                }
                $formatted[] = $name !== '' ? $name . ' <' . $email . '>' : $email;
                continue;
            }

            $address = trim((string) $address);
            if ($address !== '') {
                $formatted[] = $address;
            }
        }

        return implode(', ', $formatted);
    }

    protected function encodeJobDetails(array $payload = [], array $options = [])
    {
        $details = [
            'payload' => $this->sanitizeJobDetails($payload),
            'options' => $this->sanitizeJobDetails($options),
        ];

        $encoded = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $encoded === false ? null : $encoded;
    }

    protected function decodeJobDetails($value)
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : ['raw' => (string) $value];
    }

    protected function sanitizeJobDetails($value, $key = '', $depth = 0)
    {
        if ($depth > 6) {
            return '[nested data omitted]';
        }

        if ($key !== '' && preg_match('/password|secret|token|authorization|cookie|api[_-]?key/i', (string) $key)) {
            return '[redacted]';
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $itemKey => $itemValue) {
                $result[(string) $itemKey] = $this->sanitizeJobDetails($itemValue, (string) $itemKey, $depth + 1);
            }
            return $result;
        }

        if (is_object($value)) {
            return ['class' => get_class($value)];
        }

        if (is_string($value) && strlen($value) > 4000) {
            return substr($value, 0, 4000) . '… [truncated]';
        }

        return is_scalar($value) || $value === null ? $value : (string) $value;
    }

    protected function ensureRuntimeTables()
    {
        MigrationManager::make($this->plugin)->runMigrations();

        $schema = Capsule::schema();
        if ($schema->hasTable(self::JOB_RUNS_TABLE)
            && !$schema->hasColumn(self::JOB_RUNS_TABLE, 'details')) {
            $schema->table(self::JOB_RUNS_TABLE, function ($table) {
                $table->longText('details')->nullable();
            });
        }

        $this->ensureRetryClaimColumn();
    }

    protected function ensureRetryClaimColumn()
    {
        $schema = Capsule::schema();
        if (!$schema->hasTable(self::FAILED_JOBS_TABLE)
            || $schema->hasColumn(self::FAILED_JOBS_TABLE, 'retry_claimed_at')) {
            return;
        }

        $schema->table(self::FAILED_JOBS_TABLE, function ($table) {
            $table->timestamp('retry_claimed_at')->nullable()->after('failed_at');
            $table->index(['retry_claimed_at']);
        });
    }

    /**
     * Backfill active queue rows created before job tracking existed.
     */
    protected function syncUntrackedQueueRows($contextId = 0, $queueName = 'default')
    {
        $rows = Capsule::table(self::JOBS_TABLE)
            ->where('queue', '=', (string) $queueName)
            ->get();

        foreach ($rows as $row) {
            $exists = Capsule::table(self::JOB_RUNS_TABLE)
                ->where('queue_job_id', '=', (int) $row->id)
                ->exists();
            if ($exists) {
                continue;
            }

            $payload = json_decode((string) $row->payload, true);
            $data = is_array($payload) ? $this->extractQueueData($payload) : [];
            $jobType = isset($data['jobType']) ? (string) $data['jobType'] : 'unknown';
            $rowContextId = !empty($row->context_id)
                ? (int) $row->context_id
                : (!empty($data['contextId']) ? (int) $data['contextId'] : null);
            if ((int) $contextId > 0 && (int) $rowContextId !== (int) $contextId) {
                continue;
            }

            $queuedAt = !empty($row->created_at) ? date('Y-m-d H:i:s', (int) $row->created_at) : $this->now();
            $startedAt = !empty($row->reserved_at) ? date('Y-m-d H:i:s', (int) $row->reserved_at) : null;
            Capsule::table(self::JOB_RUNS_TABLE)->insert([
                'queue_job_id' => (int) $row->id,
                'context_id' => $rowContextId ?: null,
                'queue' => (string) $row->queue,
                'job_type' => $jobType,
                'display_name' => $this->displayName($jobType, []),
                'details' => $this->encodeJobDetails($data ?: $payload, ['source' => 'queue_backfill']),
                'status' => $startedAt ? 'processing' : 'queued',
                'progress' => 0,
                'attempts' => (int) $row->attempts,
                'available_at' => (int) $row->available_at,
                'queued_at' => $queuedAt,
                'started_at' => $startedAt,
                'created_at' => $queuedAt,
                'updated_at' => $this->now(),
            ]);
        }
    }

    /**
     * Backfill historical failures so the Failed filter and Retry action work
     * immediately after upgrading the plugin.
     */
    protected function syncUntrackedFailedRows($contextId = 0, $queueName = 'default')
    {
        $rows = Capsule::table(self::FAILED_JOBS_TABLE)
            ->where('queue', '=', (string) $queueName)
            ->get();

        foreach ($rows as $row) {
            $exists = Capsule::table(self::JOB_RUNS_TABLE)
                ->where('failed_job_id', '=', (int) $row->id)
                ->exists();
            if ($exists) {
                continue;
            }

            $payload = json_decode((string) $row->payload, true);
            $data = is_array($payload) ? $this->extractQueueData($payload) : [];
            $jobType = isset($data['jobType']) ? (string) $data['jobType'] : 'unknown';
            $rowContextId = !empty($row->context_id)
                ? (int) $row->context_id
                : (!empty($data['contextId']) ? (int) $data['contextId'] : null);
            if ((int) $contextId > 0 && (int) $rowContextId !== (int) $contextId) {
                continue;
            }

            $failedAt = $row->failed_at ?: $this->now();
            Capsule::table(self::JOB_RUNS_TABLE)->insert([
                'queue_job_id' => !empty($row->queue_job_id) ? (int) $row->queue_job_id : null,
                'failed_job_id' => (int) $row->id,
                'context_id' => $rowContextId ?: null,
                'queue' => (string) $row->queue,
                'job_type' => $jobType,
                'display_name' => $this->displayName($jobType, []),
                'details' => $this->encodeJobDetails($data ?: $payload, ['source' => 'failed_job_backfill']),
                'status' => 'failed',
                'progress' => 0,
                'stopped_at' => $failedAt,
                'error_message' => $this->safeErrorMessage($row->exception ?: 'Job failed.'),
                'created_at' => $failedAt,
                'updated_at' => $failedAt,
            ]);
        }
    }

    /**
     * Fill details for tracking rows created before payload snapshots existed.
     */
    protected function syncMissingJobDetails($contextId = 0, $queueName = 'default')
    {
        $query = Capsule::table(self::JOB_RUNS_TABLE)
            ->where('queue', '=', (string) $queueName)
            ->where(function ($builder) {
                $builder->whereNull('details')->orWhere('details', '=', '');
            });

        if ((int) $contextId > 0) {
            $query->where('context_id', '=', (int) $contextId);
        }

        foreach ($query->get() as $run) {
            $payload = null;

            if (!empty($run->queue_job_id)) {
                $queueRow = Capsule::table(self::JOBS_TABLE)
                    ->where('id', '=', (int) $run->queue_job_id)
                    ->first();
                if ($queueRow && $queueRow->payload) {
                    $payload = json_decode((string) $queueRow->payload, true);
                }
            }

            if (!is_array($payload) && !empty($run->failed_job_id)) {
                $failedRow = Capsule::table(self::FAILED_JOBS_TABLE)
                    ->where('id', '=', (int) $run->failed_job_id)
                    ->first();
                if ($failedRow && $failedRow->payload) {
                    $payload = json_decode((string) $failedRow->payload, true);
                }
            }

            if (is_array($payload)) {
                Capsule::table(self::JOB_RUNS_TABLE)
                    ->where('id', '=', (int) $run->id)
                    ->update([
                        'details' => $this->encodeJobDetails($payload, ['source' => 'tracking_backfill']),
                        'updated_at' => $this->now(),
                    ]);
            }
        }
    }

    protected function displayName($jobType, array $options)
    {
        if (!empty($options['displayName'])) {
            return trim((string) $options['displayName']);
        }

        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', (string) $jobType);
        $value = str_replace(['.', '_', '-'], ' ', $value);
        return ucwords(trim($value)) ?: 'Background job';
    }

    protected function extractQueueData(array $payload)
    {
        if (isset($payload['data']['data']) && is_array($payload['data']['data'])) {
            return $payload['data']['data'];
        }
        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }
        return [];
    }

    protected function durationSeconds($startedAt, $finishedAt)
    {
        if (!$startedAt || !$finishedAt) {
            return null;
        }
        $duration = strtotime((string) $finishedAt) - strtotime((string) $startedAt);
        return max(0, (int) $duration);
    }

    protected function safeErrorMessage($message)
    {
        $message = trim((string) $message);
        return $message === '' ? 'Job failed.' : substr($message, 0, 1000);
    }

    protected function now()
    {
        return date('Y-m-d H:i:s');
    }

    protected function formatTime($value)
    {
        return $value ? date('H:i:s', strtotime((string) $value)) : null;
    }

    protected function formatDateTime($value)
    {
        return $value ? date('c', strtotime((string) $value)) : null;
    }

    protected function formatDuration($seconds)
    {
        if ($seconds === null || $seconds === '') {
            return null;
        }

        $seconds = max(0, (int) $seconds);
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;
        if ($minutes < 60) {
            return $minutes . 'm ' . $remaining . 's';
        }
        $hours = intdiv($minutes, 60);
        return $hours . 'h ' . ($minutes % 60) . 'm';
    }

    protected function makeWorker()
    {
        $laravelContainer = Registry::get('laravelContainer');

        return new Worker(
            $laravelContainer['queue'],
            $laravelContainer['events'],
            $laravelContainer['exception.handler'],
            function () {
                return method_exists($this->plugin, 'areBackgroundJobsEnabled')
                    && !$this->plugin->areBackgroundJobsEnabled();
            }
        );
    }

    protected function bootQueueConnection()
    {
        if (self::$queueConnectionBooted) {
            return;
        }

        MigrationManager::make($this->plugin)->runMigrations();

        // Register runtime queue connection via container config.
        $laravelContainer = Registry::get('laravelContainer');
        if (isset($laravelContainer['config'])) {
            $laravelContainer['config']['queue.connections.' . self::CONNECTION] = [
                'driver' => 'database',
                'table' => self::JOBS_TABLE,
                'connection' => 'default',
                'queue' => 'default',
                'retry_after' => self::QUEUE_RETRY_AFTER_SECONDS,
            ];
        } else {
            $laravelContainer['config'] = [
                'queue.connections.' . self::CONNECTION => [
                    'driver' => 'database',
                    'table' => self::JOBS_TABLE,
                    'connection' => 'default',
                    'queue' => 'default',
                    'retry_after' => self::QUEUE_RETRY_AFTER_SECONDS,
                ],
            ];
        }

        self::$queueConnectionBooted = true;
    }

    protected function makeWorkerOptions($options, $stopWhenEmpty)
    {
        $sleep = isset($options['sleep']) ? max(0, (int) $options['sleep']) : 3;
        $tries = isset($options['tries']) ? max(1, (int) $options['tries']) : 3;
        $timeout = isset($options['timeout']) ? max(1, (int) $options['timeout']) : 60;
        $timeout = min(
            $timeout,
            self::QUEUE_RETRY_AFTER_SECONDS - self::WORKER_TIMEOUT_BUFFER_SECONDS
        );
        $memory = isset($options['memory']) ? max(64, (int) $options['memory']) : 128;

        return new WorkerOptions(
            0,
            $memory,
            $timeout,
            $sleep,
            $tries,
            false,
            $stopWhenEmpty
        );
    }

    /**
     * Resolve journal/context id from options or payload.
     *
     * @param array $options
     * @param array $payload
     * @return int|null
     */
    protected function resolveContextId($options, $payload)
    {
        if (isset($options['contextId']) && (int) $options['contextId'] > 0) {
            return (int) $options['contextId'];
        }

        if (isset($payload['contextId']) && (int) $payload['contextId'] > 0) {
            return (int) $payload['contextId'];
        }

        if (isset($payload['journalId']) && (int) $payload['journalId'] > 0) {
            return (int) $payload['journalId'];
        }

        $request = Application::get() ? Application::get()->getRequest() : null;
        $context = $request ? $request->getContext() : null;
        if ($context && (int) $context->getId() > 0) {
            return (int) $context->getId();
        }

        return null;
    }

    /**
     * Store context id in dedicated column for fast filtering.
     *
     * @param mixed $jobId
     * @param int|null $contextId
     * @return void
     */
    protected function persistContextId($jobId, $contextId)
    {
        if (!$jobId) {
            return;
        }

        if (!$contextId || (int) $contextId <= 0) {
            return;
        }

        try {
            Capsule::table(self::JOBS_TABLE)
                ->where('id', '=', (int) $jobId)
                ->update(['context_id' => (int) $contextId]);
        } catch (Throwable $e) {
            error_log('OjtWorkerBee context_id update failed for job ' . (int) $jobId . ': ' . $e->getMessage());
        }
    }
}
