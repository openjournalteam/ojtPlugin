<?php

namespace Openjournalteam\OjtPlugin\Jobs;

interface JobInterface
{
    /**
     * Unique job type string stored in queue payload.
     *
     * @return string
     */
    public function getType();

    /**
     * Default queue name.
     *
     * @return string
     */
    public function getQueue();

    /**
     * Dispatch this job through WorkerBee.
     *
     * @param array $payload
     * @param array $options
     * @return mixed
     */
    public function dispatch(array $payload = [], array $options = []);

    /**
     * Handle queued payload.
     *
     * @param array $payload
     * @param array $data
     * @return mixed
     */
    public function handle(array $payload = [], array $data = []);

    /**
     * Whether this job requires runtime base URL enrichment.
     *
     * @return bool
     */
    public function isRequireRuntimeBaseUrl();
}
