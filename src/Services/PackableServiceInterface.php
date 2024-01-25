<?php

namespace Hyn\AgoraRoomService\Services;

interface PackableServiceInterface
{
    public function getServiceType(): int;
    public function Pack($stream): void;
    public function UnPack($stream): void;
}
