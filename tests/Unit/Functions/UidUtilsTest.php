<?php

namespace Hyn\AgoraRoomServiceTests\Unit\Functions;

use PHPUnit\Framework\TestCase;

use function Hyn\AgoraRoomService\Functions\UidUtils\GetUidStr;

class UidUtilsTest extends TestCase
{
    public function test_php_zlib_compress_decompress()
    {
        $this->assertEquals(GetUidStr(123), "123", "GetUidStr");
        $this->assertEquals(GetUidStr(0), "", "GetUidStr");
    }
}
