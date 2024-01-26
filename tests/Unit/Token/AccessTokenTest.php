<?php

namespace Hyn\AgoraRoomServiceTests\Unit\Token;

use Exception;
use Hyn\AgoraRoomService\Services\ServiceRtc;
use Hyn\AgoraRoomServiceTests\Constants\DataMock;
use Hyn\AgoraRoomService\Token\AccessToken;
use Hyn\AgoraRoomService\TokenBuilderService;
use PHPUnit\Framework\TestCase;


class AccessTokenTest extends TestCase
{


    public function test_create_instance_CreateAccessToken()
    {
        $accessToken = AccessToken::CreateAccessToken(DataMock::AppId, DataMock::AppCertificate, DataMock::Expire);
        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->AppCert, DataMock::AppCertificate);
        $this->assertEquals($accessToken->Expire, DataMock::Expire);

        $accessToken = AccessToken::CreateAccessToken();
        $this->assertEquals($accessToken->AppId, '');
        $this->assertEquals($accessToken->AppCert, '');
        $this->assertEquals($accessToken->Expire, 900);
    }

    public function test_create_instance_NewAccessToken()
    {
        $accessToken = AccessToken::NewAccessToken(DataMock::AppId, DataMock::AppCertificate, DataMock::Expire);
        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->AppCert, DataMock::AppCertificate);
        $this->assertEquals($accessToken->Expire, DataMock::Expire);
    }

    public function test_isUuid()
    {
        $this->assertEquals(AccessToken::isUuid(DataMock::AppId), true);
        $this->assertEquals(AccessToken::isUuid(DataMock::AppCertificate), true);
        $this->assertEquals(AccessToken::isUuid("590f3e8a-bc22-11ee-a506-0242ac120002"), false);
        $this->assertEquals(AccessToken::isUuid(""), false);
    }


    public function test_build_fail()
    {
        $accessToken = new AccessToken('DataMock::AppCertificate', 'DataMock::AppId', DataMock::Expire, DataMock::IssueTs, DataMock::Salt, []);
        $this->expectException(Exception::class);
        $accessToken->Build();
    }

    public function test_build_fail2()
    {
        $accessToken = new AccessToken('DataMock::AppCertificate', DataMock::AppId, DataMock::Expire, DataMock::IssueTs, DataMock::Salt, []);
        $this->expectException(Exception::class);
        $accessToken->Build();
    }

    public function test_build_parse()
    {

        $accessToken = new AccessToken(DataMock::AppCertificate, DataMock::AppId, DataMock::Expire, DataMock::IssueTs, DataMock::Salt, []);
        $token = $accessToken->Build();
        $this->assertEquals($token, "007eNpTYHhp4iup/VRy+8OdVyYt4hE+3vf/V5rmW92LK2Xu8egdy7qtwGBpbuDsaGyakmpmkGxiYmZimpSUmGqRaGRoamBmmGRsrMK6KTWCiYGBkQEEAFc1Gu8=", '$token');

        $accessToken->Parse($token);

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
