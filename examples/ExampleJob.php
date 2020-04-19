<?php

class ExampleJob implements \Bobby\Queue\JobContract
{

    public function handle()
    {
        echo "start request\n";
        sleep(2);
        var_dump(file_get_contents("http://www.baidu.com"));
    }

    public function canRetry(int $attempt, $error): bool
    {
        if ($attempt > 2) {
            return  false;
        }
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
        return 1;
    }
}