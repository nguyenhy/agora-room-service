<?php

namespace Hyn\AgoraRoomService\Functions\UidUtils;

function GetUidStr($uid)
{
    if ($uid === 0) {
        return "";
    }
    return "$uid";
}
