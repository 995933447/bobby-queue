<?php
namespace Bobby\Queue\Utils;

use Bobby\Queue\Serializers\ClosureSerializer;
use Bobby\Queue\Serializers\ObjectSerializer;

trait SerializerTrait
{
    protected $objectSerializer;

    protected $closureSerializer;

    protected function getObjectSerializer(): ObjectSerializer
    {
        if (is_null($this->objectSerializer)) {
            $this->objectSerializer = new ObjectSerializer();
        }
        return $this->objectSerializer;
    }

    protected function getClosureSerializer(): ClosureSerializer
    {
        if (is_null($this->closureSerializer)) {
            $this->closureSerializer = new ClosureSerializer();
        }

        return $this->closureSerializer;
    }

    public function serialize($value)
    {
        if ($value instanceof \Closure) {
            $serializer = $this->getClosureSerializer();
        } else {
            $serializer = $this->getObjectSerializer();
        }

        return $serializer->serialize($value);
    }
}