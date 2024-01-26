<?php

namespace Hyn\AgoraRoomServiceTests\Unit\Token;

use Hyn\AgoraRoomServiceTests\Constants\DataMock;
use Hyn\AgoraRoomService\Token\AccessToken;
use PHPUnit\Framework\TestCase;


class AccessTokenTest extends TestCase
{


    public function test_BuildTokenWithUid_RolePublisher()
    {

        $accessToken = new AccessToken(DataMock::AppCertificate, DataMock::AppId, DataMock::Expire, DataMock::IssueTs, DataMock::Salt, []);
        $token = $accessToken->Build();
        $this->assertEquals($token, "007eNpTYHhp4iup/VRy+8OdVyYt4hE+3vf/V5rmW92LK2Xu8egdy7qtwGBpbuDsaGyakmpmkGxiYmZimpSUmGqRaGRoamBmmGRsrMK6KTWCiYGBkQEEAFc1Gu8=", '$token');

        $accessToken = AccessToken::CreateAccessToken();
        $accessToken->Parse($token);

        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->Expire, DataMock::Expire);
        $this->assertEmpty($accessToken->Services);
    }
}
