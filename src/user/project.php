<?php

namespace StarkInfra;

use Exception;
use EllipticCurve\PrivateKey;
use StarkCore\Utils\Checks;


class Project extends \StarkCore\Project
{
    /**
     * Cache em memória de instâncias EllipticCurve\PrivateKey indexadas pelo hash SHA-256 do PEM.
     * Evita recomputar o parse DER e a multiplicação escalar na curva secp256k1 em cada
     * instanciação de Project e em cada assinatura de requisição HTTP.
     *
     * @var array<string, PrivateKey>
     */
    private static $privateKeyCache = [];

    /** @var PrivateKey|null */
    private $cachedPrivateKey = null;

    function __construct(array $params)
    {
        $id = Checks::checkParam($params, "id");
        if (!is_null($id)) {
            $id = strval($id);
        }
        $this->id = $id;

        $pem = Checks::checkParam($params, "privateKey");
        $this->cachedPrivateKey = self::resolvePrivateKey($pem);
        $this->pem = $pem;
        $this->environment = Checks::checkEnvironment(Checks::checkParam($params, "environment"));
        $this->name = Checks::checkParam($params, "name");
        $this->allowedIps = Checks::checkParam($params, "allowedIps");

        Checks::checkParams($params);
    }

    public function privateKey()
    {
        if ($this->cachedPrivateKey instanceof PrivateKey) {
            return $this->cachedPrivateKey;
        }

        $this->cachedPrivateKey = self::resolvePrivateKey($this->pem);

        return $this->cachedPrivateKey;
    }

    public static function resolvePrivateKey($pem): PrivateKey
    {
        if (!is_string($pem) || trim($pem) === "") {
            throw new Exception("Private-key must be valid secp256k1 ECDSA string in pem format");
        }

        $cacheKey = hash("sha256", $pem);
        if (isset(self::$privateKeyCache[$cacheKey])) {
            return self::$privateKeyCache[$cacheKey];
        }

        try {
            $parsed = PrivateKey::fromPem($pem);
        } catch (Exception $e) {
            throw new Exception("Private-key must be valid secp256k1 ECDSA string in pem format");
        }

        if (count(self::$privateKeyCache) >= 128) {
            array_shift(self::$privateKeyCache);
        }

        self::$privateKeyCache[$cacheKey] = $parsed;

        return $parsed;
    }

    public static function flushPrivateKeyCache(): void
    {
        self::$privateKeyCache = [];
    }
}
