<?php

namespace Hyn\AgoraRoomServiceTests\Unit\Token;

use Hyn\AgoraRoomService\Services\ServiceRtc;
use Hyn\AgoraRoomServiceTests\Constants\DataMock;
use Hyn\AgoraRoomService\Token\AccessToken;
use Hyn\AgoraRoomService\TokenBuilderService;
use PHPUnit\Framework\TestCase;


class AccessTokenTest extends TestCase
{


    public function test_build_parse()
    {

        $accessToken = new AccessToken(DataMock::AppCertificate, DataMock::AppId, DataMock::Expire, DataMock::IssueTs, DataMock::Salt, []);
        $token = $accessToken->Build();
        $this->assertEquals($token, "007eNpTYHhp4iup/VRy+8OdVyYt4hE+3vf/V5rmW92LK2Xu8egdy7qtwGBpbuDsaGyakmpmkGxiYmZimpSUmGqRaGRoamBmmGRsrMK6KTWCiYGBkQEEAFc1Gu8=", '$token');

        $accessToken->Parse($token);

        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->Expire, DataMock::Expire);
        $this->assertEmpty($accessToken->Services);
    }

    public function test_build_privilege_parse()
    {

        $role = TokenBuilderService::RolePublisher;

        $accessToken = new AccessToken(DataMock::AppCertificate, DataMock::AppId, DataMock::Expire, DataMock::IssueTs, DataMock::Salt, []);

        $serviceRtc = ServiceRtc::NewServiceRtc(DataMock::ChannelName, DataMock::Uid);
        $serviceRtc->AddPrivilege(AccessToken::PrivilegeJoinChannel, DataMock::Expire);
        if ($role == TokenBuilderService::RolePublisher) {
            $serviceRtc->AddPrivilege(AccessToken::PrivilegePublishAudioStream, DataMock::Expire);
            $serviceRtc->AddPrivilege(AccessToken::PrivilegePublishVideoStream, DataMock::Expire);
            $serviceRtc->AddPrivilege(AccessToken::PrivilegePublishDataStream, DataMock::Expire);
        }
        $accessToken->AddService($serviceRtc);

        $token = $accessToken->Build();

        $accessToken->Parse($token);

        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->Expire, DataMock::Expire);

        $this->assertEquals(count($accessToken->Services), 1);
        $this->assertEquals(count($accessToken->Services), 1);
    }
}
