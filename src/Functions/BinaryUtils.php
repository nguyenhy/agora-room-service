<?php

namespace Hyn\AgoraRoomService\Functions\BinaryUtils;

class BinaryConfig
{
    const INT16_BYTES_LENGTH = 2;
    const INT32_BYTES_LENGTH = 4;
    const UINT16_FORMAT = 'v';
    const UINT32_FORMAT = 'V';
    const INT16_FORMAT = 's';
}

function packUint16($n)
{
    return pack(BinaryConfig::UINT16_FORMAT, $n);
}

function unPackUint16($r)
{
    return unpack(BinaryConfig::UINT16_FORMAT, $r);
}

function packUint32($n)
{
    return pack(BinaryConfig::UINT32_FORMAT, $n);
}

function unPackUint32($r)
{
    return unpack(BinaryConfig::UINT32_FORMAT, $r);
}

function packInt16($n)
{
    return pack(BinaryConfig::INT16_FORMAT, $n);
}

function unPackInt16($r)
{
    return unpack(BinaryConfig::INT16_FORMAT, $r);
}

function packString($stream, $s)
{
    $packed = packUint16(strlen($s));
    if ($packed === false) {
        return false;
    }

    $write1 = fwrite($stream, $packed);
    if ($write1 === false) {
        return false;
    }

    $write2 = fwrite($stream, $s);
    if ($write2 === false) {
        return false;
    }

    return $write2;
}

function unPackString($stream)
{
    $bytes_from_start = fread($stream, BinaryConfig::INT16_BYTES_LENGTH);
    if ($bytes_from_start === false) {
        return false;
    }

    $unpacked_bytes = unPackInt16($bytes_from_start);
    if ($unpacked_bytes === false || !isset($unpacked_bytes[1])) {
        return false;
    }

    $length = (int) $unpacked_bytes[1];
    if (!($length > 0)) {
        return false;
    }

    $data = fread($stream, $length);
    return $data;
}

function packMapUint32($stream, $map)
{
    $length = count($map);
    $packed_length = packUint16($length);
    if ($packed_length === false) {
        return false;
    }

    $write_length = fwrite($stream, $packed_length);
    if ($write_length === false) {
        return false;
    }

    $keys = array_keys($map);
    sort($keys);

    foreach ($keys as $k) {
        $packed_key     = packUint16($k);
        if ($packed_key === false) {
            return false;
        }
        $packed_value   = packUint32($map[$k]);
        if ($packed_value === false) {
            return false;
        }

        $write_key      = fwrite($stream, $packed_key);
        if ($write_key === false) {
            return false;
        }

        $write_value    = fwrite($stream, $packed_value);
        if ($write_value === false) {
            return false;
        }
    }
}

function unPackMapUint32($stream)
{
    $bytes_length = fread($stream, BinaryConfig::INT16_BYTES_LENGTH);
    if ($bytes_length === false) {
        return null;
    }
    $unpacked_length = unPackInt16($bytes_length);

    if (!isset($unpacked_length[1])) {
        return null;
    }

    $length = (int) $unpacked_length[1];
    if (!($length > 0)) {
        return null;
    }


    $data = [];
    for ($i = 0; $i < $length; $i++) {
        $key_bytes   = fread($stream, BinaryConfig::INT16_BYTES_LENGTH);
        if ($key_bytes === false) {
            return null;
        }

        $value_bytes  = fread($stream, BinaryConfig::INT32_BYTES_LENGTH);
        if ($value_bytes === false) {
            return null;
        }

        $unpacked_key        = unPackUint16($key_bytes);
        if ($unpacked_key === false) {
            return null;
        }

        $unpacked_value      = unPackUint32($value_bytes);
        if ($unpacked_value === false) {
            return null;
        }

        if (!isset($unpacked_key[1]) || !isset($unpacked_value[1])) {
            return null;
        }
        $key = $unpacked_key[1];
        $value = $unpacked_value[1];

        $data[$key] = $value;
    }
    return $data;
}
