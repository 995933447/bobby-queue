<?php
namespace Bobby\Queue\Utils;

final class QueueRedisKeyGenerator
{
    const CHANNEL_PREFIX = 'bobby-queue';

    protected $prefix;

    public function __construct(string $channel)
    {
        $this->prefix = static::CHANNEL_PREFIX . ":$channel:";
    }

    public function getKeyPrefix(): string
    {
        return $this->prefix;
    }

    public static function make(string $channel)
    {
        return new static($channel);
    }

    public function getMessageIdStringKey(): string
    {
        return "{$this->prefix}messages_id";
    }

    public function getDelayedMessagesZsetKey()
    {
        return "{$this->prefix}delayed";
    }

    public function getQueueListKey()
    {
        return "{$this->prefix}ready";
    }

    public function getMessagesHashKey()
    {
        return "{$this->prefix}messages";
    }

    public function getReservedMessagesZsetKey()
    {
        return "{$this->prefix}reserved";
    }

    public function getMessageAttemptsHashKey()
    {
        return "{$this->prefix}attempts";
    }

    public function getFailedMessageZsetMessages()
    {
        return "{$this->prefix}failed";
    }
}