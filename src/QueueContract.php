<?php
namespace Bobby\Queue;

abstract class QueueContract
{
    protected $channel;

    protected $options;

    /**
     * QueueContract constructor.
     * @param string $channel
     * @param array $options
     */
    final public function __construct(string $channel, array $options)
    {
        $this->beforeConstruct($channel, $options);

        $this->channel = $channel;
        $this->options = $options;

        $this->afterConstruct();
    }

    /**
     * @return mixed
     */
    abstract protected function beforeConstruct(string $channel, array $options);

    /**
     * @return mixed
     */
    abstract protected function afterConstruct();

    /**Get channel name
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    abstract public function size(): int;

    /**Push a job into queue
     * @param $message
     * @param int $delay
     * @return mixed
     */
    abstract public function push($message, int $delay = 0);

    /**Pop a job id from queue
     * @return int
     */
    abstract public function pop(): ?int;

    /**Get job from id.
     * @param int $id
     * @return mixed
     */
    abstract public function get(int $id);

    /**Mark message job failed.
     * @param int $id
     * @return mixed
     */
    abstract public function fail(int $id);

    /**Remove a message job
     * @param int $id
     * @return mixed
     */
    abstract public function done(int $id);

    /**Release a job which was failed to execute.
     * @param int $fd
     * @return mixed
     */
    abstract public function release(int $fd, int $delay = 0);

    /**Clear the queue.
     * @return mixed
     */
    abstract public function clear();

    /**Check status is waiting?
     * @param int $id
     * @return bool
     */
    public function isWaiting(int $id): bool
    {
        return $this->getStatus($id) === static::WAITING_STATUS;
    }

    /**Check status is done?
     * @inheritDoc
     */
    public function isDone(int $id): bool
    {
        return $this->getStatus($id) === static::DONE_STATUS;
    }

    /**Check status is failed?
     * @inheritDoc
     */
    public function isFailed(int $id): bool
    {
        return $this->getStatus($id) === static::FAILED_STATUS;
    }

    /**Check status is reserved?
     * @inheritDoc
     */
    public function isReserved(int $id): bool
    {
        return $this->getStatus($id) === static::RESERVED_STATUS;
    }

    /**Migrate the delayed jobs that are ready to the regular queue.
     * @return mixed
     */
    abstract public function migrateExpiredJobs();

    /**Get Message Attempts
     * @param int $id
     * @return int
     */
    abstract public function getMessageAttempts(int $id): int;

    /**Retry reserved message job
     * @return mixed
     */
    abstract public function retryReserved();

    /**Reset queue connection
     * @return mixed
     */
    abstract public function resetConnection();

    /**Release all failed message job
     * @return mixed
     */
    abstract public function releaseAllFailed();
}