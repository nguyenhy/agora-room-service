<?php

namespace Hyn\AgoraRoomService\Services;

use Closure;
use Exception;
use Throwable;

class ReturnerService
{
    public TaskHolderService $task;

    public function __construct()
    {
        $this->task = new TaskHolderService();
    }

    public function add($method): Closure
    {
        return $this->task->add($method);
    }

    public function run(): void
    {
        $this->task->run();
    }

    /**
     * @param Exception|string|null $exception
     * @param int $code
     * @param Throwable|null $previous
     */
    public function stop(
        $exception = null,
        $code = 0,
        $previous = null
    ) {
        $this->run();
        if ($exception) {
            if ($exception instanceof \Exception) {
                throw $exception;
            } else {
                throw new Exception($exception, $code, $previous);
            }
        } else {
            throw new Exception("ReturnerService.stop", 1);
        }
    }
}
