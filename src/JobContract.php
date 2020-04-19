<?php
namespace Bobby\Queue;

interface JobContract
{
    /**
     * Execute current job.
     *
     * @return mixed
     */
    public function handle();

    /**
     * Determine whether current job can retry if fail.
     *
     * @param int $attempt
     * @param $error
     *
     * @return bool
     */
    public function canRetry(int $attempt, $error): bool;

    /**
     * Get current job's next execution unix time after failed.
     *
     * @param int $attempt
     *
     * @return int
     */
    public function retryAfter(int $attempt): int;

    /**
     * After failed, this function will be called.
     *
     * @param int   $id
     * @param \Throwable $e
     */
    public function failed(int $id, \Throwable $e);

    /**Job execute max time.
     * @return int
     */
    public function timeoutAt(): int;
}