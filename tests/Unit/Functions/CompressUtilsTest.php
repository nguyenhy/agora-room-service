<?php

namespace AgoraRoomServiceTests\Unit\Functions;

use PHPUnit\Framework\TestCase;

use Hyn\AgoraRoomService\Functions\CompressUtils\{
    function compressZlib,
    function decompressZlib,
};


class CompressUtilsTest extends TestCase
{
    public function test_php_zlib_compress_decompress()
    {
        $target = hex2bin("78da4b4c4a4e010003d8018b");
        $this->assertEquals(compressZlib("abcd"), $target, "compressZlib php");
        $this->assertEquals(decompressZlib($target), "abcd", "decompressZlib php");
    }

    public function test_php_decompress_golang_string()
    {
        /**
         * ```go
         * package main
         * import (
         *     "bytes"
         *     "compress/zlib"
         *     "encoding/hex"
         *     "fmt"
         * )
         * 
         * func compressZlib(src []byte) []byte {
         *     var in bytes.Buffer
         *     wZlib := zlib.NewWriter(&in)
         *     wZlib.Write(src)
         *     wZlib.Close()
         *     return in.Bytes()
         * }
         * func decompressZlib(compressSrc []byte) []byte {
         *     b := bytes.NewReader(compressSrc)
         *     var out bytes.Buffer
         *     r, _ := zlib.NewReader(b)
         *     io.Copy(&out, r)
         *     return out.Bytes()
         * }
         * 
         * func main() {
         *     byteSlice := []byte("abcd")
         *     fmt.Println(hex.EncodeToString(compressZlib(byteSlice)))
         * }
         * ```
         */
        $target = hex2bin("789c4a4c4a4e01040000ffff03d8018b");
        $this->assertEquals(decompressZlib($target), "abcd", "decompressZlib php");
    }
}
