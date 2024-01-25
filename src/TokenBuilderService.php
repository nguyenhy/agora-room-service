<?php

namespace Hyn\AgoraRoomService;

use Hyn\AgoraRoomService\Services\ServiceRtc;
use Hyn\AgoraRoomService\Token\AccessToken;

use function Hyn\AgoraRoomService\Functions\UidUtils\GetUidStr;

class TokenBuilderService
{
    public const RolePublisher  = 1; // for live broadcaster
    public const RoleSubscriber = 2; // default, for live audience

    public static function BuildTokenWithUid(string $appId, string $appCertificate, string $channelName, int $uid, string $role, int $expire)
    {
        return self::BuildTokenWithAccount($appId, $appCertificate, $channelName, GetUidStr($uid), $role, $expire);
    }

    public static function BuildTokenWithAccount(string $appId, string $appCertificate, string $channelName, string $account, $role, int $expire)
    {
        $token = AccessToken::NewAccessToken($appId, $appCertificate, $expire);

        $serviceRtc = ServiceRtc::NewServiceRtc($channelName, $account);
        $serviceRtc->AddPrivilege(AccessToken::PrivilegeJoinChannel, $expire);
        if ($role == self::RolePublisher) {
            $serviceRtc->AddPrivilege(AccessToken::PrivilegePublishAudioStream, $expire);
            $serviceRtc->AddPrivilege(AccessToken::PrivilegePublishVideoStream, $expire);
            $serviceRtc->AddPrivilege(AccessToken::PrivilegePublishDataStream, $expire);
        }
        $token->AddService($serviceRtc);

        return $token->Build();
    }
}
