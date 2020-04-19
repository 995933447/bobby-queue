<?php
namespace Bobby\Queue;

use Bobby\MultiProcesses\Process;
use Bobby\Queue\QueueContract;
use Bobby\Queue\Utils\ConsoleLogger;

class ConsumerProcessesManager
{
    protected $queue;

    protected $options;

    protected $consumers;

    protected $asyncListenSignals = false;

    protected $logger;

    protected $isRunning = false;

    public function __construct(QueueContract $queue, array $options)
    {
        $this->queue = $queue;
        $this->options = $options;

        $options['logger']['channel'] = $this->queue->getChannel();
        $this->logger = new ConsoleLogger($options['logger']);
    }

    public function run()
    {
        if (isset($this->options['consumer']['daemonize']) && $this->options['consumer']['daemonize']) {
            $process = new Process(function () {
                $this->workAndListenConsumers();
            }, true);

            $process->run();

            exit;
        }

        $this->workAndListenConsumers();
    }

    protected function workAndListenConsumers()
    {
        $this->queue->retryReserved();

        $this->readyMonitorConsumers();

        $this->runConsumers();

        $this->monitorConsumers();
    }

    protected function readyMonitorConsumers()
    {
        $this->isRunning = true;

        pcntl_signal(SIGINT, function () {
            foreach ($this->consumers as $consumer) {
                posix_kill($consumer->getPid(), SIGTERM);
            }

            $this->isRunning = false;
        });

        pcntl_signal(SIGTERM, function () {
            foreach ($this->consumers as $consumer) {
                posix_kill($consumer->getPid(), SIGUSR1);
            }

            $this->isRunning = false;
        });

        pcntl_signal(SIGUSR1, function () {
            foreach ($this->consumers as $consumer) {
                posix_kill($consumer->getPid(), SIGTERM);
            }
        });

        pcntl_signal(SIGUSR2, function () {
            foreach ($this->consumers as $consumer) {
                posix_kill($consumer->getPid(), SIGUSR1);
            }
        });

        pcntl_signal(SIGALRM, function () {
            $this->queue->migrateExpiredJobs();
        });

        if (function_exists('pcntl_async_signals')) {
            if (!pcntl_async_signals()) {
                pcntl_async_signals(true);
            }
            $this->asyncListenSignals = true;
        }
    }

    protected function runConsumers()
    {
        $consumerNum = $this->options['consumer']['process_num']?? 1;
        for ($i = 0; $i < $consumerNum; $i++) {
            $this->createConsumer();
        }
    }

    protected function createConsumer()
    {
        $this->consumers[] = $consumer = new ConsumerProcess($this->queue, $this->options);
        $consumer->run();
    }

    protected function monitorConsumers()
    {
        while (1) {
            $this->dispatchSignals();

            $pid = pcntl_wait($status, WNOHANG);

            if (!$this->isRunning) {
                break;
            }

            if ($pid > 0) {
                foreach ($this->consumers as $index => $consumer) {
                    if ($consumer->getPid() === $pid) {
                        unset($this->consumers[$index]);
                        $this->createConsumer();
                    }
                }
            } else {
                sleep(1);
            }
        }
    }

    protected function dispatchSignals()
    {
        pcntl_alarm(1);

        if (!$this->asyncListenSignals) {
            pcntl_signal_dispatch();
        }
    }
}