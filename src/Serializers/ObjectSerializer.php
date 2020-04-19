<?php
namespace Bobby\Queue\Serializers;

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