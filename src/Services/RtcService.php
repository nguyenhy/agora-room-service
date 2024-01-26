<?php

namespace Hyn\AgoraRoomService\Services;

use Hyn\AgoraRoomService\Token\AccessToken;

use function Hyn\AgoraRoomService\Functions\BinaryUtils\packMapUint32;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\packString;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\packUint16;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\unPackMapUint32;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\unPackString;

class ServiceRtc implements PackableServiceInterface
{
    public Service $Service;
    public string $ChannelName;
    public string $Uid;

    public function __construct(string $ChannelName, string $Uid, Service $Service)
    {
        $this->Service = $Service;
        $this->ChannelName = $ChannelName;
        $this->Uid = $Uid;
    }

    public static function NewServiceRtc(string $channelName, string $uid): ServiceRtc
    {
        return new ServiceRtc($channelName, $uid, Service::NewService(AccessToken::ServiceTypeRtc));
    }

    /**
     * pack orders
     * - Type
     * - Privileges
     * - ChannelName
     * - Uid
     */
    function Pack($stream): void
    {
        $this->Service->Pack($stream);
        packString($stream, $this->ChannelName);
        packString($stream, $this->Uid);
    }

    /**
     * unpack orders
     * - Type
     * - Privileges
     * - ChannelName
     * - Uid
     */
    function UnPack($stream): void
    {
        $this->Service->UnPack($stream);
        $this->ChannelName = unPackString($stream);
        $this->Uid = unPackString($stream);
    }

    function AddPrivilege(int $privilege, int $expire)
    {
        $this->Service->AddPrivilege($privilege, $expire);
    }

    function getServiceType(): int
    {
        return $this->Service->getServiceType();
    }


    function packPrivileges($stream)
    {
        return $this->Service->packPrivileges($stream);
    }

    function packType($stream)
    {
        return $this->Service->packType($stream);
    }
}
