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

    public function stop(
        Exception|string|null $exception = null,
        int $code = 0,
        Throwable|null $previous = null
    ) {
        $this->task->run();
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
