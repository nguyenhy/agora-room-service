<?php

namespace Hyn\AgoraRoomService\Token;

use Exception;
use Hyn\AgoraRoomService\Functions\BinaryUtils\BinaryConfig;
use Hyn\AgoraRoomService\Services\PackableServiceInterface;
use Hyn\AgoraRoomService\Services\ReturnerService;
use Hyn\AgoraRoomService\Services\Service;
use Hyn\AgoraRoomService\Services\ServiceRtc;

use function Hyn\AgoraRoomService\Functions\Base64Utils\base64DecodeStr;
use function Hyn\AgoraRoomService\Functions\Base64Utils\base64EncodeStr;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\packString;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\packUint16;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\packUint32;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\unPackString;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\unPackUint16;
use function Hyn\AgoraRoomService\Functions\BinaryUtils\unPackUint32;
use function Hyn\AgoraRoomService\Functions\CompressUtils\compressZlib;
use function Hyn\AgoraRoomService\Functions\CompressUtils\decompressZlib;

class AccessToken
{
    const Version       = "007";
    const VersionLength = 3;

    // Service type
    const ServiceTypeRtc       = 1;
    const ServiceTypeRtm       = 2;
    const ServiceTypeFpa       = 4;
    const ServiceTypeChat      = 5;
    const ServiceTypeEducation = 7;

    // Rtc
    const PrivilegeJoinChannel        = 1;
    const PrivilegePublishAudioStream = 2;
    const PrivilegePublishVideoStream = 3;
    const PrivilegePublishDataStream  = 4;

    // Rtm
    // Fpa
    const PrivilegeLogin = 1;

    // Chat
    const PrivilegeChatUser = 1;
    const PrivilegeChatApp  = 2;

    // Education
    const PrivilegeEducationRoomUser = 1;
    const PrivilegeEducationUser     = 2;
    const PrivilegeEducationApp      = 3;

    public string $AppCert;
    public string $AppId;
    public int $Expire;
    public int $IssueTs;
    public int $Salt;
    /**
     * map[uint16]IService
     * @var array<int,IService>
     */
    public array $Services;

    public function __construct(

        string $AppCert,
        string $AppId,
        int $Expire,
        int $IssueTs,
        int $Salt,
        /**
         * map[uint16]IService
         * @var array<int,IService>
         */
        array $Services
    ) {
        $this->AppCert = $AppCert;
        $this->AppId = $AppId;
        $this->Expire = $Expire;
        $this->IssueTs = $IssueTs;
        $this->Salt = $Salt;
        $this->Services = $Services;
    }

    static public function NewAccessToken(string $appId, string $appCert, int $expire): AccessToken
    {
        // Get the current Unix timestamp in seconds
        $issueTs = (int)time();

        // Generate a random salt between 1 and 99999999
        $salt = mt_rand(1, 99999999);

        return new AccessToken($appCert, $appId, $expire, $issueTs, $salt, []);
    }

    static function CreateAccessToken(string $appId = "", string $appCert = "", int  $expire = 900): AccessToken
    {
        return self::NewAccessToken($appId, $appCert, $expire);
    }

    static function isUuid(string $s): bool
    {
        if (strlen($s) !== 32) {
            return false;
        }
        return hex2bin($s) !== false;
    }

    static function getVersion()
    {
        return self::Version;
    }

    function AddService(PackableServiceInterface $service)
    {
        $this->Services[$service->getServiceType()] = $service;
    }

    function Build()
    {

        $returner = new ReturnerService();

        if (!self::isUuid($this->AppId)) {
            $returner->stop("AccessToken.appId");
        }

        if (!self::isUuid($this->AppCert)) {
            $returner->stop("AccessToken.appCertificate");
        }

        $buf = fopen('php://memory', 'r+');
        if ($buf === false) {
            return $returner->stop('AccessToken.packedAppId');
        }

        $returner->add(function () use ($buf) {
            fclose($buf);
        });

        $packedAppId = packString($buf, $this->AppId);
        if ($packedAppId === false) {
            $returner->stop("AccessToken.packedAppId");
        }

        $packedIssueTs = packUint32($this->IssueTs);
        if ($packedIssueTs === false) {
            $returner->stop("AccessToken.packedIssueTs");
        }

        $writeIssueTs = fwrite($buf, $packedIssueTs);
        if ($writeIssueTs === false) {
            $returner->stop("AccessToken.writeIssueTs");
        }


        $packedExpire = packUint32($this->Expire);
        if ($packedExpire === false) {
            $returner->stop("AccessToken.packedExpire");
        }

        $writeExpire = fwrite($buf, $packedExpire);
        if ($writeExpire === false) {
            $returner->stop("AccessToken.writeExpire");
        }

        $packedSalt = packUint32($this->Salt);
        if ($packedSalt === false) {
            $returner->stop("AccessToken.packedSalt");
        }

        $writeSalt = fwrite($buf, $packedSalt);
        if ($writeSalt === false) {
            $returner->stop("AccessToken.writeSalt");
        }

        $packedServices = packUint16(count($this->Services));
        if ($packedServices === false) {
            $returner->stop("AccessToken.packedServices");
        }

        $writeServices = fwrite($buf, $packedServices);
        if ($writeServices === false) {
            $returner->stop("AccessToken.writeServices");
        }


        // Sign
        $sign = $this->getSign();
        if ($sign === false) {
            $returner->stop("AccessToken.sign");
        }

        // Pack services in definite order

        $serviceTypes = [self::ServiceTypeRtc, self::ServiceTypeRtm, self::ServiceTypeFpa, self::ServiceTypeChat, self::ServiceTypeEducation];
        $Services = $this->Services;
        foreach ($serviceTypes as $serviceType) {
            $service = isset($Services[$serviceType]) ?  $Services[$serviceType] : null;
            if ($service) {
                $packed = $service->Pack($buf);
                if ($packed === false) {
                    $returner->stop("AccessToken.Pack");
                }
            }
        }

        // // Signature
        // hSign := hmac.New(sha256.New, sign)
        // hSign.Write(buf.Bytes())
        // signature := hSign.Sum(nil)

        rewind($buf);
        $bufBytes = stream_get_contents($buf);
        if ($bufBytes === false) {
            $returner->stop("AccessToken.bufBytes");
        }

        $hSignContext = hash_init('sha256', HASH_HMAC, $sign);
        hash_update($hSignContext, $bufBytes);
        $signature = hash_final($hSignContext);

        // bufContent := new(bytes.Buffer)
        // if err = packString(bufContent, string(signature)); err != nil {
        // 	return
        // }
        $bufContent = fopen('php://memory', 'r+');
        if ($bufContent === false) {
            $returner->stop("AccessToken.bufContent");
        }
        $returner->add(function () use ($bufContent) {
            fclose($bufContent);
        });

        $packedString = packString($bufContent, hex2bin($signature));
        if ($packedString === false) {
            $returner->stop("AccessToken.packedString");
        }

        // bufContent.Write(buf.Bytes())
        fwrite($bufContent, $bufBytes);



        rewind($bufContent);
        $bufContentBytes = stream_get_contents($bufContent);
        if ($bufContentBytes === false) {
            $returner->stop("AccessToken.bufContentBytesCompressed");
        }

        $bufContentBytesCompressed = compressZlib($bufContentBytes);
        if ($bufContentBytesCompressed === false) {
            $returner->stop("AccessToken.bufContentBytesCompressed");
        }

        // res = getVersion() + base64EncodeStr(compressZlib(bufContent.Bytes()))
        $res = self::getVersion() . base64EncodeStr($bufContentBytesCompressed);

        $returner->run();

        return $res;
    }

    function Parse(string $token): bool
    {
        $returner = new ReturnerService();

        // version := token[:VersionLength]
        // if version != getVersion() {
        //  return
        // }
        $version = substr($token, 0, self::VersionLength);
        if ($version != self::getVersion()) {
            $returner->stop("AccessToken.version");
        }

        // var decodeByte []byte
        // if decodeByte, err = base64DecodeStr(token[VersionLength:]); err != nil {
        //  return
        // }
        $decodeByte = base64DecodeStr(substr($token, self::VersionLength));
        $decodeByteDecompressed = decompressZlib($decodeByte);

        // buffer := bytes.NewReader(decompressZlib(decodeByte))
        $buffer  = fopen('php://memory', 'r+');
        if ($buffer === false) {
            $returner->stop("AccessToken.bufContent");
        }
        $returner->add(function () use ($buffer) {
            fclose($buffer);
        });

        $bufferWrite = fwrite($buffer, $decodeByteDecompressed);
        if ($bufferWrite === false) {
            $returner->stop("AccessToken.bufferWrite");
        }

        // // signature
        // _, err = unPackString(buffer)
        // if err != nil {
        // 	return
        // }
        rewind($buffer);
        $unpackedString = unPackString($buffer);
        if ($unpackedString === false) {
            $returner->stop("AccessToken.unpackedString");
        }

        // if accessToken.AppId, err = unPackString(buffer); err != nil {
        // 	return
        // }
        $unpackedAppId = unPackString($buffer);
        if ($unpackedAppId === false) {
            $returner->stop("AccessToken.unpackedAppId");
        }
        $this->AppId = $unpackedAppId;


        // if accessToken.IssueTs, err = unPackUint32(buffer); err != nil {
        // 	return
        // }
        $bytesIssueTs  = fread($buffer, BinaryConfig::INT32_BYTES_LENGTH);
        if ($bytesIssueTs === false) {
            $returner->stop("AccessToken.BytesIssueTs");
        }

        // rewind($buffer);
        $unpackedIssueTs = unPackUint32($bytesIssueTs);
        if ($unpackedIssueTs === false || !isset($unpackedIssueTs[1])) {
            $returner->stop("AccessToken.unpackedIssueTs");
        }
        $this->IssueTs = (int) $unpackedIssueTs[1];

        // if accessToken.Expire, err = unPackUint32(buffer); err != nil {
        // 	return
        // }
        $bytesExpire  = fread($buffer, BinaryConfig::INT32_BYTES_LENGTH);
        if ($bytesExpire === false) {
            $returner->stop("AccessToken.bytesExpire");
        }

        $unpackedExpire = unPackUint32($bytesExpire);
        if ($unpackedExpire === false || !isset($unpackedExpire[1])) {
            $returner->stop("AccessToken.unpackedExpire");
        }
        $this->Expire = (int) $unpackedExpire[1];

        // if accessToken.Salt, err = unPackUint32(buffer); err != nil {
        // 	return
        // }
        $bytesSalt  = fread($buffer, BinaryConfig::INT32_BYTES_LENGTH);
        if ($bytesSalt === false) {
            $returner->stop("AccessToken.bytesSalt");
        }

        $unpackedSalt = unPackUint32($bytesSalt);
        if ($unpackedSalt === false || !isset($unpackedSalt[1])) {
            $returner->stop("AccessToken.unpackedSalt");
        }
        $this->Salt = (int) $unpackedSalt[1];


        // var serviceNum uint16
        // if serviceNum, err = unPackUint16(buffer); err != nil {
        // 	return
        // }
        $bytesServiceNum  = fread($buffer, BinaryConfig::INT16_BYTES_LENGTH);
        if ($bytesServiceNum === false) {
            $returner->stop("AccessToken.bytesServiceNum");
        }

        $unpackedServiceNum = unPackUint16($bytesServiceNum);
        if ($unpackedServiceNum === false || !isset($unpackedServiceNum[1])) {
            $returner->stop("AccessToken.unpackedServiceNum");
        }
        $serviceNum = (int) $unpackedServiceNum[1];

        // var serviceType uint16
        // for i := 0; i < int(serviceNum); i++ {
        for ($i = 0; $i < $serviceNum; $i++) {
            /**
             * ```go
             * if serviceType, err = unPackUint16(buffer); err != nil {         (1)
             *     return
             * }
             * service := accessToken.newService(serviceType)
             * if err = service.UnPack(buffer); err != nil {
             *     return
             * }
             * accessToken.Services[serviceType] = service
             * ```
             * - get `serviceType`
             * - then call `accessToken.newService(serviceType)` to get expected service
             * - call `service.UnPack(buffer)` to unpack the `Privileges` only
             * - set value to `accessToken.Services` with key is `serviceType` and value is `service`
             * 
             * but in this php implementation, we'll do it a little differently:
             * - `Pack` and `UnPack` method of `Hyn\AgoraRoomService\Services\Service`
             * will always pack/unpack `Type` and `Privileges` (golang version pack `Type` and `Privileges` and unpack `Privileges` only)
             * - Since the order of bytes sequence is always `Type->Privileges->ChannelName->Uid`, we will first
             *    - call `UnPack` method of `Hyn\AgoraRoomService\Services` to unpack `Type->Privileges`
             *    - base on the `Type` we will create a new service of `ServiceRtc`, `ServiceRtm`, ....
             */

            $service = Service::NewService(-1);
            $service->UnPack($buffer);
            $serviceType = $service->Type;

            switch ($serviceType) {
                case self::ServiceTypeRtc:
                    $ChannelName = unPackString($buffer);
                    $Uid = unPackString($buffer);
                    $rtcService = new ServiceRtc($ChannelName, $Uid, $service);
                    $this->Services[$serviceType] = $rtcService;
                    break;
                case self::ServiceTypeRtm:
                    // return self::NewServiceRtm("");
                    break;
                case self::ServiceTypeFpa:
                    // return self::NewServiceFpa();
                    break;
                case self::ServiceTypeChat:
                    // return self::NewServiceChat("");
                    break;
                case self::ServiceTypeEducation:
                    // return self::NewServiceEducation("", "", -1);
                    break;
                default:
                    break;
            }
        }
        // }


        $returner->run();

        // return true, nil
        return true;
    }

    function getSign()
    {
        $returner = new ReturnerService();
        // bufIssueTs := new(bytes.Buffer)
        $bufIssueTs = fopen('php://memory', 'r+');
        if ($bufIssueTs === false) {
            $returner->stop("AccessToken.bufIssueTs");
        }
        $returner->add(function () use ($bufIssueTs) {
            fclose($bufIssueTs);
        });

        // err = packUint32(bufIssueTs, accessToken.IssueTs)
        // if err != nil {
        // return
        // }
        $packedIssueTs = packUint32($this->IssueTs);
        if ($packedIssueTs === false) {
            $returner->stop("AccessToken.packedIssueTs");
        }
        $writeIssueTs  = fwrite($bufIssueTs, $packedIssueTs);
        if ($writeIssueTs === false) {
            $returner->stop("AccessToken.writeIssueTs");
        }

        rewind($bufIssueTs);
        $bufIssueTsContent = stream_get_contents($bufIssueTs);

        // hIssueTs := hmac.New(sha256.New, bufIssueTs.Bytes())
        // hIssueTs.Write([]byte(accessToken.AppCert))
        $hIssueTsContext = hash_init('sha256', HASH_HMAC, $bufIssueTsContent);
        hash_update($hIssueTsContext, $this->AppCert);
        $hIssueTs = hash_final($hIssueTsContext);
        if ($hIssueTs === false) {
            $returner->stop("AccessToken.hIssueTs");
        }


        // // Salt
        // $bufSalt = new(bytes.Buffer)
        $bufSalt = fopen('php://memory', 'r+');
        if ($bufSalt === false) {
            $returner->stop("AccessToken.bufContent");
        }
        $returner->add(function () use ($bufSalt) {
            fclose($bufSalt);
        });

        // err = packUint32(bufSalt, accessToken.Salt)
        // if err != nil {
        // 	return
        // }
        $packedSalt = packUint32($this->Salt);
        if ($packedSalt === false) {
            $returner->stop("AccessToken.packedSalt");
        }
        $writeSalt = fwrite($bufSalt, $packedSalt);
        if ($writeSalt === false) {
            $returner->stop("AccessToken.writeSalt");
        }

        rewind($bufSalt);
        $bufSaltContent = stream_get_contents($bufSalt);

        // $hSalt = hmac.New(sha256.New, bufSalt.Bytes())
        // hSalt.Write([]byte(hIssueTs.Sum(nil)))
        $hSaltContext = hash_init('sha256', HASH_HMAC, $bufSaltContent);
        hash_update($hSaltContext, hex2bin($hIssueTs));
        $hSalt = hash_final($hSaltContext);

        $returner->run();

        return hex2bin($hSalt);
    }
}
