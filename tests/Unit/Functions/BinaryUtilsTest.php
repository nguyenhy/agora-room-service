<?php

namespace AgoraRoomServiceTests\Unit\Functions;

use PHPUnit\Framework\TestCase;

use  AgoraRoomService\Functions\BinaryUtils\{
    BinaryConfig,
    function packUint16,
    function packUint32
};

use function AgoraRoomService\Functions\BinaryUtils\{
    packInt16,
    packMapUint32,
    packString,
    unPackInt16,
    unPackMapUint32,
    unPackString,
    unPackUint16,
    unPackUint32,
};

class BinaryUtilsTest extends TestCase
{
    public function testPackUint16()
    {
        $buffer = fopen('php://memory', 'r+');

        $data = packUint16(600);
        fwrite($buffer, $data);

        rewind($buffer);
        $bytes_from_start = fread($buffer, BinaryConfig::INT16_BYTES_LENGTH);

        $this->assertEquals(bin2hex($bytes_from_start), "5802", "packUint16");

        $data2 = unPackUint16($bytes_from_start)[1];

        $this->assertEquals($data2, 600, "unPackUint16");

        // Close the buffer
        fclose($buffer);
    }

    public function testPackUint32()
    {
        $buffer = fopen('php://memory', 'r+');

        $data = packUint32(600);
        fwrite($buffer, $data);

        rewind($buffer);
        $bytes_from_start = fread($buffer, BinaryConfig::INT32_BYTES_LENGTH);

        $this->assertEquals(bin2hex($bytes_from_start), "58020000", "packUint32");

        $data2 = unPackUint32($bytes_from_start)[1];

        $this->assertEquals($data2, 600, "unPackUint32");

        // Close the buffer
        fclose($buffer);
    }

    public function testPackInt16()
    {
        $buffer = fopen('php://memory', 'r+');

        $data = packInt16(-1);
        fwrite($buffer, $data);

        rewind($buffer);
        $bytes_from_start = fread($buffer, BinaryConfig::INT32_BYTES_LENGTH);

        $data2 = unPackInt16($bytes_from_start)[1];

        $this->assertEquals($data2, -1, "unPackInt16");

        // Close the buffer
        fclose($buffer);
    }

    public function testPackString()
    {
        $buffer = fopen('php://memory', 'r+');

        packString($buffer, "hello");

        rewind($buffer);
        $str = stream_get_contents($buffer);
        $this->assertEquals(bin2hex($str), "050068656c6c6f", "packString");


        rewind($buffer);
        $content = unPackString($buffer);
        $this->assertEquals($content, 'hello', "unPackString");

        // Close the buffer
        fclose($buffer);
    }

    public function testPackMapUint32()
    {
        $buffer = fopen('php://memory', 'r+');
        $map = [1 => 2];

        packMapUint32($buffer, $map);

        rewind($buffer);
        $str = stream_get_contents($buffer);
        $this->assertEquals(bin2hex($str), "0100010002000000", "packMapUint32");


        rewind($buffer);
        $content = unPackMapUint32($buffer);
        $this->assertEquals(json_encode($content), json_encode($map), "unPackString");

        // Close the buffer
        fclose($buffer);
    }
}
