<?php
namespace Bobby\Queue\Serializers;

interface SerializerContract
{
    public function serialize($value);

    public function unserialize($value);
}