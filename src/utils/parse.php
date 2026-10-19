<?php

namespace StarkInfra\Utils;

use Exception;
use EllipticCurve\Ecdsa;
use EllipticCurve\PublicKey;
use EllipticCurve\Signature;
use StarkInfra\Settings;
use StarkCore\Error\InvalidSignatureError;
use StarkCore\Utils\Cache;


class Parse
{
    public static function parseAndVerify($content, $signature, $resource, $user)
    {
        $content = self::verify($content, $signature, $user);
        $json = json_decode($content, true);
        $entity = $json;
        if ($resource["name"] == "Event") {
            $entity = $json[API::lastName($resource["name"])];
        }

        return API::fromApiJson($resource["maker"], $entity);
    }

    public static function verify($content, $signature, $user = null)
    {
        try {
            $parsedSignature = Signature::fromBase64($signature);
        } catch (Exception $e) {
            throw new InvalidSignatureError("The provided signature is not valid");
        }

        $resolvedUser = Settings::getUser($user);
        $language = Settings::getLanguage();
        $timeout = Settings::getTimeout();

        $publicKey = self::getPublicKey($resolvedUser, $language, $timeout, false);
        if (self::verifySignature($content, $parsedSignature, $publicKey)) {
            return $content;
        }

        $publicKey = self::getPublicKey($resolvedUser, $language, $timeout, true);
        if (self::verifySignature($content, $parsedSignature, $publicKey)) {
            return $content;
        }

        throw new InvalidSignatureError("The provided signature and content do not match the Stark public key");
    }

    private static function verifySignature($content, $signature, $publicKey): bool
    {
        return Ecdsa::verify($content, $signature, $publicKey);
    }

    private static function getPublicKey($user, $language, $timeout, $refresh = false)
    {
        $publicKey = Cache::getStarkPublicKey();
        if (!is_null($publicKey) && !$refresh) {
            return $publicKey;
        }

        $pem = Request::fetch(
            Rest::getHost(),
            Rest::getSdkVersion(),
            $user,
            "GET",
            "/public-key",
            null,
            ["limit" => 1],
            Rest::getApiVersion(),
            $language,
            $timeout
        )->json()["publicKeys"][0]["content"];

        $publicKey = PublicKey::fromPem($pem);
        Cache::setStarkPublicKey($publicKey);

        return $publicKey;
    }
}
