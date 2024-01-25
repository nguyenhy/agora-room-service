<?php

namespace AgoraRoomServiceTests\Unit;

use Hyn\AgoraRoomService\Token\AccessToken;
use PHPUnit\Framework\TestCase;


class AccessTokenTest extends TestCase
{
    const DataMockAccount        = "^ZSgT<%q:Fj*@`92>#OHL?\"hkm~nGYiP";
    const DataMockAppCertificate = "5CFd2fd1755d40ecb72977518be15d3b";
    const DataMockAppId          = "970CA35de60c44645bbae8a215061b33";
    const DataMockChannelName    = "7d72365eb983485397e3e3f9d460bdda";
    const DataMockExpire         = 600;
    const DataMockUid            = 2882341273;
    const DataMockUidStr         = "2882341273";
    const DataMockIssueTs        = 1706165540;
    const DataMockSalt           = 1;


    public function test_BuildTokenWithUid_RolePublisher()
    {

        $accessToken = new AccessToken(self::DataMockAppCertificate, self::DataMockAppId, self::DataMockExpire, self::DataMockIssueTs, self::DataMockSalt, []);
        $token = $accessToken->Build();
        $this->assertEquals($token, "007eNpTYHhp4iup/VRy+8OdVyYt4hE+3vf/V5rmW92LK2Xu8egdy7qtwGBpbuDsaGyakmpmkGxiYmZimpSUmGqRaGRoamBmmGRsrMK6KTWCiYGBkQEEAFc1Gu8=", '$token');

        $accessToken = AccessToken::CreateAccessToken();
        $accessToken->Parse($token);

        $this->assertEquals($accessToken->AppId, self::DataMockAppId);
        $this->assertEquals($accessToken->AppId, self::DataMockAppId);
        $this->assertEquals($accessToken->Expire, self::DataMockExpire);
        $this->assertEmpty($accessToken->Services);
    }
}
