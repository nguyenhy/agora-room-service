<?php

namespace Hyn\AgoraRoomService\Token;

use Exception;
use Hyn\AgoraRoomService\Functions\BinaryUtils\BinaryConfig;
use Hyn\AgoraRoomService\Services\PackableServiceInterface;
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

    function Build(): string
    {

        if (!self::isUuid($this->AppId)) {
            throw new Exception("AccessToken.appId");
        }

        if (!self::isUuid($this->AppCert)) {
            throw new Exception("AccessToken.appCertificate");
        }


        $buf = fopen('php://memory', 'r+');
        $packedAppId = packString($buf, $this->AppId);
        if ($packedAppId === false) {
            throw new Exception("AccessToken.packedAppId", 1);
        }

        $packedIssueTs = packUint32($this->IssueTs);
        if ($packedIssueTs === false) {
            throw new Exception("AccessToken.packedIssueTs", 1);
        }

        $writeIssueTs = fwrite($buf, $packedIssueTs);
        if ($writeIssueTs === false) {
            throw new Exception("AccessToken.writeIssueTs", 1);
        }


        $packedExpire = packUint32($this->Expire);
        if ($packedExpire === false) {
            throw new Exception("AccessToken.packedExpire", 1);
        }

        $writeExpire = fwrite($buf, $packedExpire);
        if ($writeExpire === false) {
            throw new Exception("AccessToken.writeExpire", 1);
        }

        $packedSalt = packUint32($this->Salt);
        if ($packedSalt === false) {
            throw new Exception("AccessToken.packedSalt", 1);
        }

        $writeSalt = fwrite($buf, $packedSalt);
        if ($writeSalt === false) {
            throw new Exception("AccessToken.writeSalt", 1);
        }

        $packedServices = packUint16(count($this->Services));
        if ($packedServices === false) {
            throw new Exception("AccessToken.packedServices", 1);
        }

        $writeServices = fwrite($buf, $packedServices);
        if ($writeServices === false) {
            throw new Exception("AccessToken.writeServices", 1);
        }


        // Sign
        $sign = $this->getSign();
        if ($sign === false) {
            throw new Exception("AccessToken.sign", 1);
        }

        // Pack services in definite order

        $serviceTypes = [self::ServiceTypeRtc, self::ServiceTypeRtm, self::ServiceTypeFpa, self::ServiceTypeChat, self::ServiceTypeEducation];
        $Services = $this->Services;
        foreach ($serviceTypes as $serviceType) {
            $service = isset($Services[$serviceType]) ?  $Services[$serviceType] : null;
            if ($service) {
                $packed = $service->Pack($buf);
                if ($packed === false) {
                    throw new Exception("AccessToken.Pack", 1);
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
            throw new Exception("AccessToken.bufBytes", 1);
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
            throw new Exception("AccessToken.bufContent", 1);
        }

        $packedString = packString($bufContent, hex2bin($signature));
        if ($packedString === false) {
            throw new Exception("AccessToken.packedString", 1);
        }

        // bufContent.Write(buf.Bytes())
        fwrite($bufContent, $bufBytes);



        rewind($bufContent);
        $bufContentBytes = stream_get_contents($bufContent);
        if ($bufContentBytes === false) {
            throw new Exception("AccessToken.bufContentBytesCompressed", 1);
        }

        $bufContentBytesCompressed = compressZlib($bufContentBytes);
        if ($bufContentBytesCompressed === false) {
            throw new Exception("AccessToken.bufContentBytesCompressed", 1);
        }

        // res = getVersion() + base64EncodeStr(compressZlib(bufContent.Bytes()))
        $res = self::getVersion() . base64EncodeStr($bufContentBytesCompressed);

        return $res;
    }

    function Parse(string $token): bool
    {

        // version := token[:VersionLength]
        // if version != getVersion() {
        //  return
        // }
        $version = substr($token, 0, self::VersionLength);
        if ($version != self::getVersion()) {
            throw new Exception("AccessToken.version", 1);
        }

        // var decodeByte []byte
        // if decodeByte, err = base64DecodeStr(token[VersionLength:]); err != nil {
        //  return
        // }
        $decodeByte = base64DecodeStr(substr($token, self::VersionLength));
        $decodeByteDecompressed = decompressZlib($decodeByte);

        // buffer := bytes.NewReader(decompressZlib(decodeByte))
        $buffer  = fopen('php://memory', 'r+');
        $bufferWrite = fwrite($buffer, $decodeByteDecompressed);
        if ($bufferWrite === false) {
            throw new Exception("AccessToken.bufferWrite", 1);
        }

        // // signature
        // _, err = unPackString(buffer)
        // if err != nil {
        // 	return
        // }
        rewind($buffer);
        $unpackedString = unPackString($buffer);
        if ($unpackedString === false) {
            throw new Exception("AccessToken.unpackedString", 1);
        }

        // if accessToken.AppId, err = unPackString(buffer); err != nil {
        // 	return
        // }
        $unpackedAppId = unPackString($buffer);
        if ($unpackedAppId === false) {
            throw new Exception("AccessToken.unpackedAppId", 1);
        }
        $this->AppId = $unpackedAppId;


        // if accessToken.IssueTs, err = unPackUint32(buffer); err != nil {
        // 	return
        // }
        $bytesIssueTs  = fread($buffer, BinaryConfig::INT32_BYTES_LENGTH);
        if ($bytesIssueTs === false) {
            throw new Exception("AccessToken.BytesIssueTs", 1);
        }

        // rewind($buffer);
        $unpackedIssueTs = unPackUint32($bytesIssueTs);
        if ($unpackedIssueTs === false || !isset($unpackedIssueTs[1])) {
            throw new Exception("AccessToken.unpackedIssueTs", 1);
        }
        $this->IssueTs = (int) $unpackedIssueTs[1];

        // if accessToken.Expire, err = unPackUint32(buffer); err != nil {
        // 	return
        // }
        $bytesExpire  = fread($buffer, BinaryConfig::INT32_BYTES_LENGTH);
        if ($bytesExpire === false) {
            throw new Exception("AccessToken.bytesExpire", 1);
        }

        $unpackedExpire = unPackUint32($bytesExpire);
        if ($unpackedExpire === false || !isset($unpackedExpire[1])) {
            throw new Exception("AccessToken.unpackedExpire", 1);
        }
        $this->Expire = (int) $unpackedExpire[1];

        // if accessToken.Salt, err = unPackUint32(buffer); err != nil {
        // 	return
        // }
        $bytesSalt  = fread($buffer, BinaryConfig::INT32_BYTES_LENGTH);
        if ($bytesSalt === false) {
            throw new Exception("AccessToken.bytesSalt", 1);
        }

        $unpackedSalt = unPackUint32($bytesSalt);
        if ($unpackedSalt === false || !isset($unpackedSalt[1])) {
            throw new Exception("AccessToken.unpackedSalt", 1);
        }
        $this->Salt = (int) $unpackedSalt[1];


        // var serviceNum uint16
        // if serviceNum, err = unPackUint16(buffer); err != nil {
        // 	return
        // }
        $bytesServiceNum  = fread($buffer, BinaryConfig::INT16_BYTES_LENGTH);
        if ($bytesServiceNum === false) {
            throw new Exception("AccessToken.bytesServiceNum", 1);
        }

        $unpackedServiceNum = unPackUint16($bytesServiceNum);
        if ($unpackedServiceNum === false || !isset($unpackedServiceNum[1])) {
            throw new Exception("AccessToken.unpackedServiceNum", 1);
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

        // return true, nil
        return true;
    }

    function getSign()
    {
        // bufIssueTs := new(bytes.Buffer)
        $bufIssueTs = fopen('php://memory', 'r+');
        if ($bufIssueTs === false) {
            throw new Exception("AccessToken.bufIssueTs", 1);
        }

        // err = packUint32(bufIssueTs, accessToken.IssueTs)
        // if err != nil {
        // return
        // }
        $packedIssueTs = packUint32($this->IssueTs);
        if ($packedIssueTs === false) {
            fclose($bufIssueTs);
            throw new Exception("AccessToken.packedIssueTs", 1);
        }
        $writeIssueTs  = fwrite($bufIssueTs, $packedIssueTs);
        if ($writeIssueTs === false) {
            fclose($bufIssueTs);
            throw new Exception("AccessToken.writeIssueTs", 1);
        }

        rewind($bufIssueTs);
        $bufIssueTsContent = stream_get_contents($bufIssueTs);

        // hIssueTs := hmac.New(sha256.New, bufIssueTs.Bytes())
        // hIssueTs.Write([]byte(accessToken.AppCert))
        $hIssueTsContext = hash_init('sha256', HASH_HMAC, $bufIssueTsContent);
        hash_update($hIssueTsContext, $this->AppCert);
        $hIssueTs = hash_final($hIssueTsContext);
        if ($hIssueTs === false) {
            fclose($bufIssueTs);
            throw new Exception("AccessToken.hIssueTs", 1);
        }


        // // Salt
        // $bufSalt = new(bytes.Buffer)
        $bufSalt = fopen('php://memory', 'r+');

        // err = packUint32(bufSalt, accessToken.Salt)
        // if err != nil {
        // 	return
        // }
        $packedSalt = packUint32($this->Salt);
        if ($packedSalt === false) {
            fclose($bufIssueTs);
            fclose($bufSalt);
            throw new Exception("AccessToken.packedSalt", 1);
        }
        $writeSalt = fwrite($bufSalt, $packedSalt);
        if ($writeSalt === false) {
            fclose($bufIssueTs);
            fclose($bufSalt);
            throw new Exception("AccessToken.writeSalt", 1);
        }

        rewind($bufSalt);
        $bufSaltContent = stream_get_contents($bufSalt);

        // $hSalt = hmac.New(sha256.New, bufSalt.Bytes())
        // hSalt.Write([]byte(hIssueTs.Sum(nil)))
        $hSaltContext = hash_init('sha256', HASH_HMAC, $bufSaltContent);
        hash_update($hSaltContext, hex2bin($hIssueTs));
        $hSalt = hash_final($hSaltContext);

        fclose($bufIssueTs);
        fclose($bufSalt);

        return hex2bin($hSalt);
    }
}
