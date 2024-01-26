<?php

namespace Hyn\AgoraRoomServiceTests\Unit\Services;

use Hyn\AgoraRoomService\Services\ServiceRtc;
use Hyn\AgoraRoomService\Token\AccessToken;
use Hyn\AgoraRoomServiceTests\Constants\DataMock;
use PHPUnit\Framework\TestCase;

use function Hyn\AgoraRoomService\Functions\BinaryUtils\packString;

class RtcServiceTest extends TestCase
{

    public function test_create_instance()
    {
        $instance = ServiceRtc::NewServiceRtc(
            DataMock::ChannelName,
            DataMock::Uid
        );


        $this->assertEquals($instance->ChannelName, DataMock::ChannelName);
        $this->assertEquals($instance->Uid, DataMock::Uid);

        $instance->AddPrivilege(AccessToken::PrivilegeJoinChannel, DataMock::Expire);

        $this->assertArrayHasKey(AccessToken::PrivilegeJoinChannel, $instance->Service->Privileges);
        $this->assertEquals($instance->Service->Privileges[AccessToken::PrivilegeJoinChannel], DataMock::Expire);

        $this->assertEquals($instance->getServiceType(), AccessToken::ServiceTypeRtc);
    }

    public function test_pack()
    {
        $instance = ServiceRtc::NewServiceRtc(
            DataMock::ChannelName,
            DataMock::Uid
        );

        $target = hex2bin("01000000200037643732333635656239383334383533393765336533663964343630626464610a0032383832333431323733");
        $buffer = fopen('php://memory', 'r+');

        $instance->Pack($buffer);

        rewind($buffer);
        $content = stream_get_contents($buffer);

        $this->assertEquals($content, $target);
    }

    public function test_pack_manual()
    {
        $instance = ServiceRtc::NewServiceRtc(
            DataMock::ChannelName,
            DataMock::Uid
        );

        $target = hex2bin("01000000200037643732333635656239383334383533393765336533663964343630626464610a0032383832333431323733");
        $buffer = fopen('php://memory', 'r+');

        $instance->packType($buffer);
        $instance->packPrivileges($buffer);
        packString($buffer, $instance->ChannelName);
        packString($buffer, $instance->Uid);

        rewind($buffer);
        $content = stream_get_contents($buffer);

        $this->assertEquals($content, $target);
    }

    public function test_unpack()
    {
        $instance = ServiceRtc::NewServiceRtc("", 0);

        $target = hex2bin("01000000200037643732333635656239383334383533393765336533663964343630626464610a0032383832333431323733");
        $buffer = fopen('php://memory', 'r+');
        fwrite($buffer, $target);
        rewind($buffer);

        $instance->UnPack($buffer);


        $this->assertEquals($instance->ChannelName, DataMock::ChannelName, 'ChannelName');
        $this->assertEquals($instance->Uid, DataMock::Uid, 'Uid');
    }
}
