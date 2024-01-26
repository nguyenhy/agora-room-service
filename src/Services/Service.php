<?php

namespace Hyn\AgoraRoomService\Services;

use Exception;
use Hyn\AgoraRoomService\Functions\BinaryUtils\BinaryConfig;

use function Hyn\AgoraRoomService\Functions\BinaryUtils\packMapUint32;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\packUint16;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\unPackMapUint32;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\unPackUint16;

class Service  implements PackableServiceInterface
{
    /**
     * map[uint16]uint32
     * @var array<int,int>
     */
    public array $Privileges;
    public int $Type;

    public function __construct(array $Privileges, int $Type)
    {
        $this->Privileges = $Privileges;
        $this->Type = $Type;
    }

    public static function NewService(int $serviceType): Service
    {
        return new Service([], $serviceType);
    }

    function AddPrivilege(int $privilege, int $expire)
    {
        $this->Privileges[$privilege] = $expire;
    }

    function getServiceType(): int
    {
        return $this->Type;
    }

    /**
     * @throw Exception\InvalidArgumentException
     */
    function Pack($stream): void
    {
        $this->packType($stream);
        $this->packPrivileges($stream);
    }

    function UnPack($stream): void
    {
        $this->unPackType($stream);
        $this->unPackPrivileges($stream);
    }

    function packPrivileges($stream)
    {
        $packed = packMapUint32($stream, $this->Privileges);
        if ($packed === false) {
            throw new Exception("packPrivileges.packMapUint32");
        }

        return $packed;
    }

    function unPackPrivileges($stream)
    {
        $unpacked = unpackMapUint32($stream);
        if (!is_array($unpacked)) {
            throw new Exception("packPrivileges.packMapUint32");
        }
        $this->Privileges = $unpacked;
    }

    function packType($stream)
    {
        $packed =  packUint16($this->Type);
        if ($packed === false) {
            throw new Exception("packType.packUint16");
        }

        $write = fwrite($stream, $packed);
        if ($write === false) {
            throw new Exception("packType.fwrite");
        }

        return $write;
    }

    function unPackType($stream)
    {
        $read = fread($stream, BinaryConfig::INT16_BYTES_LENGTH);
        if ($read === false) {
            throw new Exception("unPackType.fread");
        }

        $unpacked =  unPackUint16($read);
        if ($unpacked === false || !isset($unpacked[1])) {
            throw new Exception("unPackType.unPackUint16");
        }

        $this->Type = $unpacked[1];
    }
}
