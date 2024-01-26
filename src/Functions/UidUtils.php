<?php

namespace Hyn\AgoraRoomService\Functions\UidUtils;

function GetUidStr(int $uid)
{
    if ($uid === 0) {
        return "";
    }
    return "$uid";
}
