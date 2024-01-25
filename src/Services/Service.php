<?php

namespace Hyn\AgoraRoomService\Services;

use Exception;

use function Hyn\AgoraRoomService\Functions\BinaryUtils\packMapUint32;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\packUint16;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\unPackMapUint32;

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
        return new  Service([],  $serviceType);
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
        $map = unPackMapUint32($stream);
        if ($map === false) {
            throw new Exception("UnPack.unPackMapUint32");
        }
        $this->Privileges = $map;
    }

    function packPrivileges($stream)
    {
        return packMapUint32($stream, $this->Privileges);
    }

    function packType($stream)
    {
        return packUint16($stream, $this->Type);
    }
}
