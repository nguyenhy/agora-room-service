<?php

namespace Hyn\AgoraRoomService\Services;

use Closure;
use Throwable;

class TaskHolderService
{
    private array $methods = [];

    public function add($method): Closure
    {
        $this->methods[] = $method;

        return function () use ($method) {
            $this->remove($method);
        };
    }

    public function remove($method): void
    {
        $index = array_search($method, $this->methods);
        if ($index !== false) {
            array_splice($this->methods, $index, 1);
        }
    }

    public function run(): void
    {
        $methods = $this->methods;
        foreach ($methods as $index => $element) {
            if (!$element) {
                continue;
            }
            try {
                $element();
                $methods[$index] = null;
            } catch (Throwable $error) {
                echo $error->getMessage() . PHP_EOL;
            }
        }
        $this->methods = [];
    }

    public function destroy(): void
    {
        $this->run();
    }
}
