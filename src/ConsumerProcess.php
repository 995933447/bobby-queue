<?php
namespace Bobby\Queue;

use Bobby\MultiProcesses\Process;
use Bobby\MultiProcesses\Quit;
use Bobby\Queue\QueueContract;
use Bobby\Queue\Utils\ConsoleLogger;

class ConsumerProcess extends Process
{
    protected $queue;

    protected $options;

    protected $isRunning = false;

    protected $asyncListenSignals = false;

    protected $logger;

    public function __construct(QueueContract $queue, array $options)
    {
        $this->queue = $queue;
        $this->options = $options;

        $options['logger']['channel'] = $this->queue->getChannel();
        $this->logger = new ConsoleLogger($options['logger']);

        parent::__construct(function () {
            $this->work();
        }, false);
    }

    public function work()
    {
        $this->init();

        $this->dispatchSignals();

        while ($this->isRunning) {
            $id = $this->queue->pop();

            if (is_null($id)) {
                sleep($this->options['sleep_seconds']?? 1);
                continue;
            }

            $this->logger->debug("processing message job id: $id");

            $this->runMessageJob($id);

            if ($this->ifUsedMemoryExceed()) {
                $this->isRunning = false;
                continue;
            }

            $this->dispatchSignals();
        }
    }

    protected function dispatchSignals()
    {
        if (!$this->asyncListenSignals) {
            pcntl_signal_dispatch();
        }
    }

    protected function runMessageJob($messageId)
    {
        $messageJob = $this->queue->get($messageId);
        if (!$messageJob instanceof \Closure && !$messageJob instanceof JobContract) {
            return;
        }

        try {
            if ($messageJob instanceof \Closure) {
                $messageJob();
            } else {
                $startTime = time();
                register_tick_function(function () use ($startTime, $messageJob, $messageId) {
                    if ($messageJob->timeoutAt() > 0 && time() - $startTime > $messageJob->timeoutAt()) {
                        throw new \RuntimeException("Message id:$messageId is time out.");
                    }
                });

                declare(ticks = 1);

                $messageJob->handle();

                declare(ticks = 0);
            }

            $this->doneMessageJob($messageId);
        } catch (\Throwable $e) {
            $this->logger->error($e);

            if ($messageJob instanceof JobContract) {
                $messageJob->failed($messageId, $e);

                if ($messageJob->canRetry($attempts = $this->queue->getMessageAttempts($messageId), $e)) {
                    $this->queue->release($messageId, $messageJob->retryAfter($attempts));
                    return;
                }
            }

            $this->queue->fail($messageId);
        }
    }

    protected function doneMessageJob(int $messageId)
    {
        $this->queue->done($messageId);
        $this->logger->debug("Processed message job id: $messageId");
    }

    protected function ifUsedMemoryExceed(): bool
    {
        if (isset($this->options['consumer']['limit_memory'])) {
            return memory_get_usage() >= $this->options['consumer']['limit_memory'];
        }

        return false;
    }

    protected function init()
    {
        $this->queue->resetConnection();

        $this->installSignals();

        $this->isRunning = true;
    }

    protected function installSignals()
    {
        pcntl_signal(SIGTERM, function () {
            Quit::normalQuit();
        });

        pcntl_signal(SIGUSR1, function () {
            $this->isRunning = false;
        });

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals() || pcntl_async_signals(true);
            $this->asyncListenSignals = true;
        }
    }
}