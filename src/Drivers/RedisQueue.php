<?php
namespace Bobby\Queue\Drivers;

use Bobby\Queue\JobContract;
use Bobby\Queue\QueueContract;
use Bobby\Queue\Connections\RedisPool;
use Bobby\Queue\Utils\SerializerTrait;
use Predis\Client;
use Bobby\Queue\SerializedMessage;
use Bobby\Queue\Utils\QueueRedisKeyGenerator;

class RedisQueue extends QueueContract
{
    use SerializerTrait;

    const WAITING_STATUS = 1;

    const DONE_STATUS = 2;

    const FAILED_STATUS = 3;

    const RESERVED_STATUS = 4;

    protected $connectionPool;

    protected $connection;

    protected $redisKeyGenerator;

    /**
     * @inheritDoc
     */
    protected function beforeConstruct(string $channel, array $options)
    {
        $this->redisKeyGenerator = QueueRedisKeyGenerator::make($channel);
    }

    /**
     * @inheritDoc
     */
    protected function afterConstruct()
    {
        $this->resetConnection();
    }

    public function size(): int
    {
        try {
            return $this->getConnection()->llen($this->redisKeyGenerator->getQueueListKey())
                + $this->getConnection()->zlexcount($this->redisKeyGenerator->getDelayedMessagesZsetKey(), '-', '+')
                + $this->getConnection()->zlexcount($this->redisKeyGenerator->getReservedMessagesZsetKey(), '-', '+');
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    /**
     * @inheritDoc
     */
    public function push($message, int $delay = 0)
    {
        if (!$message instanceof \Closure && !$message instanceof JobContract) {
            throw new \InvalidArgumentException(gettype($message) . ' type message is not allowed.');
        }

        $serializedMessage = SerializedMessage::make(
            $message instanceof \Closure? SerializedMessage::CLOSURE_MESSAGE_TYPE: SerializedMessage::OBJECT_MESSAGE_TYPE,
            $this->serialize($message)
        );

        try {
            $this->saveMessage($this->createMessageId(), $serializedMessage, $delay);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    protected function saveMessage(int $id, SerializedMessage $serializedMessage, int $delay)
    {
        $this->getConnection()->hset(
            $this->redisKeyGenerator->getMessagesHashKey(),
            $id,
            $this->serialize($serializedMessage)
        );

        if ($delay > 0) {
            $this->getConnection()->zadd($this->redisKeyGenerator->getDelayedMessagesZsetKey(), [$id => time() + $delay]);
        } else {
            $this->getConnection()->lpush($this->redisKeyGenerator->getQueueListKey(), [$id]);
        }
    }

    protected function createMessageId(): int
    {
        return $this->getConnection()->incr($this->redisKeyGenerator->getMessageIdStringKey());
    }

    protected function getConnection(): Client
    {
        if (is_null($this->connection)) {
            $this->connection = $this->connectionPool->pop();
        }
        return $this->connection;
    }

    protected function releaseConnection(Client $connection = null)
    {
        if (is_null($connection)) {
            $connection = $this->getConnection();
        }

        $this->connectionPool->recycle($connection);

        $this->connection = null;
    }

    /**
     * @inheritDoc
     */
    public function pop(): ?int
    {
        try {
            $id = $this->retrieveNextMessage();
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }

        return $id;
    }

    protected function retrieveNextMessage(): ?int
    {
        try {
            if (!(int)$id = $this->getConnection()->rpop($this->redisKeyGenerator->getQueueListKey())) {
                return null;
            }

            $this->getConnection()->zadd($this->redisKeyGenerator->getReservedMessagesZsetKey(), [$id => time()]);
            $this->getConnection()->hincrby($this->redisKeyGenerator->getMessageAttemptsHashKey(), $id, 1);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }

        return (int)$id;
    }

    /**
     * @inheritDoc
     */
    public function get(int $id)
    {
        if ($serializedMessage = $this->getConnection()->hget($this->redisKeyGenerator->getMessagesHashKey(), $id)) {
            $serializedMessage = $this->getObjectSerializer()->unserialize($serializedMessage);
        }

        if ($serializedMessage instanceof SerializedMessage) {
            $serializer = $serializedMessage->isClosureMessage()? $this->getClosureSerializer(): $this->getObjectSerializer();
            return $serializer->unserialize($serializedMessage->getSerializedMessage());
        }

        return null;
    }

    public function done(int $id)
    {
        try {
            $this->getConnection()->zrem($this->redisKeyGenerator->getReservedMessagesZsetKey(), $id);
            $this->getConnection()->zrem($this->redisKeyGenerator->getFailedMessageZsetMessages(), $id);
            $this->getConnection()->hdel($this->redisKeyGenerator->getMessageAttemptsHashKey(), [$id]);
            $this->getConnection()->hdel($this->redisKeyGenerator->getMessagesHashKey(), [$id]);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    public function clear()
    {
        try {
            $this->getConnection()->del(["{$this->redisKeyGenerator->getKeyPrefix()}:*"]);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    public function fail(int $id)
    {
        try {
            $this->getConnection()->zadd($this->redisKeyGenerator->getFailedMessageZsetMessages(), [$id => time()]);
            $this->getConnection()->zrem($this->redisKeyGenerator->getReservedMessagesZsetKey(), $id);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    public function release(int $id, int $delay = 0)
    {
        try {
            if ($delay > 0) {
                $this->getConnection()->zadd($this->redisKeyGenerator->getDelayedMessagesZsetKey(), [$id => time() + $delay]);
            } else {
                $this->getConnection()->lpush($this->redisKeyGenerator->getQueueListKey(), [$id]);
            }

            if ($this->getConnection()->zrem($this->redisKeyGenerator->getFailedMessageZsetMessages(), $id)) {
                $this->getConnection()->hdel($this->redisKeyGenerator->getMessageAttemptsHashKey(), [$id]);
            } else {
                $this->getConnection()->zrem($this->redisKeyGenerator->getReservedMessagesZsetKey(), $id);
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    protected function getStatus(int $id): int
    {
        $status = static::DONE_STATUS;

        if ($this->getConnection()->hexists($this->redisKeyGenerator->getMessagesHashKey(), $id)) {
            $status = static::WAITING_STATUS;
        }

        if ($this->getConnection()->zscore($this->redisKeyGenerator->getReservedMessagesZsetKey(), $id)) {
            $status = static::RESERVED_STATUS;
        }

        if ($this->getConnection()->zscore($this->redisKeyGenerator->getFailedMessageZsetMessages(), $id)) {
            $status = static::FAILED_STATUS;
        }

        return $status;
    }

    /**
     * @inheritDoc
     */
    public function isWaiting(int $id): bool
    {
        return $this->getStatus($id) === static::WAITING_STATUS;
    }

    /**
     * @inheritDoc
     */
    public function isDone(int $id): bool
    {
        return $this->getStatus($id) === static::DONE_STATUS;
    }

    /**
     * @inheritDoc
     */
    public function isFailed(int $id): bool
    {
        return $this->getStatus($id) === static::FAILED_STATUS;
    }

    /**
     * @inheritDoc
     */
    public function isReserved(int $id): bool
    {
        return $this->getStatus($id) === static::RESERVED_STATUS;
    }

    /**
     * @inheritDoc
     */
    public function migrateExpiredJobs()
    {
        try {
            $expiredIds = $this->getConnection()->zrangebyscore(
                $delayedMessagesKey = $this->redisKeyGenerator->getDelayedMessagesZsetKey(),
                0,
                time()
            );

            if ($expiredIds) {
                foreach ($expiredIds as $id) {
                    $this->getConnection()->lpush($this->redisKeyGenerator->getQueueListKey(), [$id]);
                    $this->getConnection()->zrem($delayedMessagesKey, $id);
                }
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    public function getMessageAttempts(int $id): int
    {
        try {
            return (int)$this->getConnection()->hget($this->redisKeyGenerator->getMessageAttemptsHashKey(), $id);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    public function retryReserved()
    {
        try {
            $ids = $this->getConnection()->zrange($this->redisKeyGenerator->getReservedMessagesZsetKey(), 0, -1);
            if ($ids) {
                foreach ($ids as $id) {
                    $this->release($id);
                }
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }

    public function resetConnection()
    {
        $this->connectionPool = new RedisPool($this->options['connection']?? []);
    }

    public function releaseAllFailed()
    {
        try {
            $ids = $this->getConnection()->zrange($this->redisKeyGenerator->getFailedMessageZsetMessages(), 0, -1);
            if ($ids) {
                foreach ($ids as $id) {
                    $this->release($id);
                }
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->releaseConnection();
        }
    }
}