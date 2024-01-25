<?php

namespace Hyn\AgoraRoomService\Functions\CompressUtils;

function compressZlib($src)
{
    $compressed = gzcompress($src, 9);
    return $compressed;
}

function decompressZlib($compressSrc)
{
    $decompressed = gzuncompress($compressSrc);
    return $decompressed;
}
