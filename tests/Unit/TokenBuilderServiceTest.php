<?php

namespace Hyn\AgoraRoomServiceTests\Unit;

use Hyn\AgoraRoomServiceTests\Constants\DataMock;
use Hyn\AgoraRoomService\Token\AccessToken;
use Hyn\AgoraRoomService\TokenBuilderService;
use PHPUnit\Framework\TestCase;


class TokenBuilderServiceTest extends TestCase
{


    public function test_BuildTokenWithUid_RolePublisher()
    {
        $token = TokenBuilderService::BuildTokenWithUid(DataMock::AppId, DataMock::AppCertificate, DataMock::ChannelName, DataMock::Uid, TokenBuilderService::RolePublisher, DataMock::Expire);

        $accessToken = AccessToken::NewAccessToken(DataMock::AppId, DataMock::AppCertificate, DataMock::Expire);
        $accessToken->Parse($token);

        $this->assertEquals($accessToken->AppId, DataMock::AppId);
        $this->assertEquals($accessToken->Expire, DataMock::Expire);


        $this->assertArrayHasKey(AccessToken::ServiceTypeRtc, $accessToken->Services);
        $this->assertEquals(isset($accessToken->Services[AccessToken::ServiceTypeRtc]), true);

        /**
         * @var \Hyn\AgoraRoomService\Services\ServiceRtc
         */
        $rtcService = $accessToken->Services[AccessToken::ServiceTypeRtc];
        $this->assertEquals('Hyn\AgoraRoomService\Services\ServiceRtc', get_class($rtcService));

        $this->assertEquals($rtcService->ChannelName, DataMock::ChannelName);
    }
}
