<?php
namespace Bobby\Queue\Serializers;

use SuperClosure\Serializer;

class ClosureSerializer implements SerializerContract
{
    protected $handler;

    public function __construct()
    {
        $this->handler = new Serializer();
    }

    public function serialize($value)
    {
        return $this->handler->serialize($value);
    }

    public function unserialize($value)
    {
        return $this->handler->unserialize($value);
    }
}