<?php
namespace Bobby\Queue\Serializers;

use Bobby\Queue\SerializerContract;

class ObjectSerializer implements SerializerContract
{

    public function serialize($value)
    {
        return serialize($value);
    }

    public function unserialize($value)
    {
        return unserialize($value);
    }
}