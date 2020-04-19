<?php
require __DIR__ ."/../vendor/autoload.php";

class ExampleJob implements \Bobby\Queue\JobContract
{

    public function handle()
    {
        file_get_contents("http://www.baidu.com");
    }

    public function canRetry(int $attempt, $error): bool
    {
        return true;
    }

    public function retryAfter(int $attempt): int
    {
       return 2;
    }

    public function failed(int $id, \Throwable $e)
    {

    }

    public function timeoutAt(): int
    {
        return -1;
    }
}