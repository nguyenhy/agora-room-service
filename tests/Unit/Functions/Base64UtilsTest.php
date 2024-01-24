<?php

namespace AgoraRoomServiceTests\Unit\Functions;

use PHPUnit\Framework\TestCase;

use function AgoraRoomService\Functions\Base64Utils\{
    base64EncodeStr,
    base64DecodeStr
};

class Base64UtilsTest extends TestCase
{
    public function testSimpleEncodeDecode()
    {
        $this->assertEquals(base64EncodeStr('abcd'), "YWJjZA==", "base64encode");
        $this->assertEquals(base64DecodeStr('YWJjZA=='), "abcd", "base64DecodeStr");
    }
}
