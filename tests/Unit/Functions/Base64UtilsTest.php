<?php

namespace Hyn\AgoraRoomServiceTests\Unit\Functions;

use PHPUnit\Framework\TestCase;

use function Hyn\AgoraRoomService\Functions\Base64Utils\base64EncodeStr;
use function Hyn\AgoraRoomService\Functions\Base64Utils\base64DecodeStr;

class Base64UtilsTest extends TestCase
{
    public function testSimpleEncodeDecode()
    {
        $this->assertEquals(base64EncodeStr('abcd'), "YWJjZA==", "base64encode");
        $this->assertEquals(base64DecodeStr('YWJjZA=='), "abcd", "base64DecodeStr");
    }
}
