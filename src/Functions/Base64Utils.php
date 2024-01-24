<?php

namespace AgoraRoomService\Functions\Base64Utils;

function base64EncodeStr($src)
{
    return base64_encode($src);
}

function base64DecodeStr($s)
{
    return base64_decode($s);
}
