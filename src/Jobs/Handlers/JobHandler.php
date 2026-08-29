<?php

namespace Openjournalteam\OjtPlugin\Jobs\Handlers;

use Exception;
use HookRegistry;
use Throwable;

class JobHandler
{
    /**
     * Laravel queue job entrypoint.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     * @param array $data
     * @return void
     * @throws Throwable
     */
    public function fire($job, $data)
    {
        $jobType = isset($data['jobType']) ? (string) $data['jobType'] : '';
        $payload = isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : [];
        $handled = false;
        $result = null;
        $error = null;

        try {
            HookRegistry::call('OjtPlugin::beforeProcessJob', [&$jobType, &$payload, &$data, &$job]);
            HookRegistry::call('OjtPlugin::processJob', [&$jobType, &$payload, &$handled, &$result, &$job, &$data]);

            if (!$handled) {
                throw new Exception('No handler registered for WorkerBee job type: ' . $jobType);
            }

            HookRegistry::call('OjtPlugin::afterProcessJob', [&$jobType, &$payload, &$result, &$job, &$data]);
            $job->delete();
        } catch (Throwable $th) {
            $error = $th;
            HookRegistry::call('OjtPlugin::jobFailed', [&$jobType, &$payload, &$error, &$job, &$data]);

            // Respect per-job max attempts from payload so jobs can fail immediately
            // (Laravel-style failed job) when requested by dispatcher.
            $maxAttempts = isset($data['maxAttempts']) ? max(1, (int) $data['maxAttempts']) : null;
            if ($maxAttempts !== null && method_exists($job, 'attempts')) {
                $attempts = (int) $job->attempts();
                if ($attempts >= $maxAttempts && method_exists($job, 'fail')) {
                    $job->fail($th);
                    return;
                }
            }

            throw $th;
        }
    }
}