<?php
namespace Bobby\Queue;

interface SerializerContract
{
    public function serialize($value);

    public function unserialize($value);
}