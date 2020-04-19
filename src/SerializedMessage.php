<?php
namespace Bobby\Queue;

class SerializedMessage
{
    const OBJECT_MESSAGE_TYPE = 0;

    const CLOSURE_MESSAGE_TYPE = 1;

    protected $messageType;

    protected $serializedMessage;

    private function __construct(int $messageType, string $serializedMessage)
    {
        $this->messageType = $messageType;
        $this->serializedMessage = $serializedMessage;
    }

    public static function make(int $messageType, string $serializedMessage)
    {
        return new static($messageType, $serializedMessage);
    }

    public function isClosureMessage(): bool
    {
        return $this->messageType == static::CLOSURE_MESSAGE_TYPE;
    }

    public function isObjectMessage(): bool
    {
        return $this->messageType == static::OBJECT_MESSAGE_TYPE;
    }

    public function getSerializedMessage(): string
    {
        return $this->serializedMessage;
    }
}