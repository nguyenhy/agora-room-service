<?php

namespace Hyn\AgoraRoomServiceTests\Unit\Services;

use Exception;
use Hyn\AgoraRoomService\Services\TaskHolderService;
use PHPUnit\Framework\TestCase;

class TaskHolderServiceTest extends TestCase
{
    public function testAddAndRunActions()
    {
        // Arrange
        $task = new TaskHolderService();

        // Act
        $removeCallback1 = $task->add(function () {
            echo 1;
        });

        $removeCallback2 = $task->add(function () {
            echo 2;
        });

        ob_start();
        $task->run();
        $actualOutput = ob_get_clean();

        // Assert
        $this->assertSame("12", $actualOutput);

        // Clean up
        $removeCallback1();
        $removeCallback2();
    }



    public function testRemoveAction()
    {
        // Arrange
        $task = new TaskHolderService();

        // Act
        $removeCallback = $task->add(function () {
            echo 1;
        });

        $task->remove(function () {
            echo 2;
        });

        $task3 = function () {
            echo 3;
        };

        $removeCallback = $task->add($task3);
        $task->remove($task3);

        ob_start();
        $task->run();
        $actualOutput = ob_get_clean();

        // Assert
        $this->assertSame("1", $actualOutput);

        // Clean up
        $removeCallback();
    }

    public function testExceptionHandling()
    {
        // Arrange
        $task = new TaskHolderService();
        $expectedOutput = "Error occurred!\n";

        // Act
        $removeCallback = $task->add(function () use ($expectedOutput) {
            throw new Exception('Error occurred!');
        });

        ob_start();
        $task->run();
        $actualOutput = ob_get_clean();

        // Assert
        $this->assertSame($expectedOutput, $actualOutput);

        // Clean up
        $removeCallback();
    }

    public function testDestroy()
    {
        // Arrange
        $task = new TaskHolderService();

        // Act
        $removeCallback = $task->add(function () {
            echo 1;
        });

        $task->destroy();

        ob_start();
        $task->run();
        $actualOutput = ob_get_clean();

        // Assert
        $this->assertEmpty($actualOutput);

        // Clean up
        $removeCallback();
    }
}
