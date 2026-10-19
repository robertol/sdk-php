<?php

namespace StarkInfra\Utils;

use Closure;
use CurlHandle;
use Exception;
use EllipticCurve\Ecdsa;
use StarkCore\Environment;
use StarkCore\PublicUser;
use StarkCore\Error\InputErrors;
use StarkCore\Error\UnknownError;
use StarkCore\Error\InternalServerError;
use StarkCore\Utils\Checks;
use StarkCore\Utils\Response;
use StarkCore\Utils\StarkHost;
use StarkCore\Utils\URL;


class Request
{
    private const MAX_POOL_SIZE_PER_HOST = 8;
    private const DEFAULT_CONNECT_TIMEOUT = 5;
    private const DNS_CACHE_TTL_SECONDS = 300;
    private const TCP_KEEPIDLE_SECONDS = 30;
    private const TCP_KEEPINTVL_SECONDS = 15;

    /**
     * Pool LIFO de CurlHandle ociosos agrupados por origin (scheme + host).
     * Evita compartilhamento simultâneo de um mesmo CurlHandle entre corrotinas Swoole,
     * preservando conexões TCP/TLS/HTTP2 abertas (Keep-Alive) entre requisições sequenciais.
     *
     * @var array<string, array<int, CurlHandle>>
     */
    private static $handlePool = [];

    /**
     * Callback opcional para interceptar o transporte HTTP em testes unitários.
     * Assinatura: fn(string $method, array $headers, string $url, ?string $body, int $timeout): Response
     *
     * @var Closure|null
     */
    private static $customTransport = null;

    public static function setTransport(?Closure $transport): void
    {
        self::$customTransport = $transport;
    }

    public static function resetPool(): void
    {
        self::$handlePool = [];
        self::$customTransport = null;
    }

    public static function poolSize(?string $origin = null): int
    {
        if (!is_null($origin)) {
            return isset(self::$handlePool[$origin]) ? count(self::$handlePool[$origin]) : 0;
        }

        $total = 0;
        foreach (self::$handlePool as $handles) {
            $total += count($handles);
        }

        return $total;
    }

    public static function fetch(
        $host,
        $sdkVersion,
        $user,
        $method,
        $path,
        $payload = null,
        $query = null,
        $apiVersion = "v2",
        $language = "en-US",
        $timeout = 15,
        $prefix = null,
        $throwError = true
    ) {
        $user = Checks::checkUser($user);
        $language = Checks::checkLanguage($language);

        $service = [
            StarkHost::infra => "starkinfra",
            StarkHost::bank => "starkbank",
            StarkHost::sign => "starksign",
        ][$host];

        $baseUrl = [
            Environment::production => "https://api.{$service}.com/",
            Environment::sandbox => "https://sandbox.api.{$service}.com/",
        ][$user->environment];

        $url = $baseUrl . $apiVersion . "/" . ltrim((string) $path, "/");
        if (!is_null($query)) {
            $url .= self::encodeQuery($query);
        }

        $agent = "PHP-" . phpversion() . "-SDK-" . $host . "-" . $sdkVersion;
        if (!is_null($prefix)) {
            $agent = $prefix . "-PHP-" . phpversion() . "-SDK-" . $host . "-" . $sdkVersion;
        }

        $body = null;
        if (!is_null($payload)) {
            $body = json_encode($payload);
        }

        $headers = [
            "User-Agent" => $agent,
            "Accept-Language" => $language,
            "Content-Type" => "application/json",
            "Connection" => "keep-alive",
        ];
        $headers = array_merge($headers, self::_authenticationHeaders($user, $body));

        $effectiveTimeout = is_numeric($timeout) && intval($timeout) > 0 ? intval($timeout) : 15;

        $response = self::makeRequest(
            strtoupper((string) $method),
            $headers,
            $url,
            $body,
            $effectiveTimeout,
            rtrim($baseUrl, "/")
        );

        if ($throwError != false) {
            if ($response->status == 500) {
                throw new InternalServerError();
            }
            if ($response->status == 400) {
                $decoded = $response->json();
                $errors = is_array($decoded) && isset($decoded["errors"]) && is_array($decoded["errors"])
                    ? $decoded["errors"]
                    : [];
                throw new InputErrors($errors);
            }
            if ($response->status != 200) {
                throw new UnknownError(strval($response->content));
            }
        }

        return $response;
    }

    private static function makeRequest(
        string $method,
        array $headers,
        string $url,
        ?string $body,
        int $timeout,
        string $origin
    ): Response {
        if (self::$customTransport instanceof Closure) {
            return (self::$customTransport)($method, $headers, $url, $body, $timeout);
        }

        $handle = self::acquireHandle($origin);

        try {
            $headerLines = ["Expect:"];
            foreach ($headers as $key => $value) {
                $headerLines[] = $key . ": " . $value;
            }

            $connectTimeout = min(self::DEFAULT_CONNECT_TIMEOUT, $timeout);

            $options = [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headerLines,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                CURLOPT_NOSIGNAL => true,
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_FRESH_CONNECT => false,
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => self::TCP_KEEPIDLE_SECONDS,
                CURLOPT_TCP_KEEPINTVL => self::TCP_KEEPINTVL_SECONDS,
                CURLOPT_DNS_CACHE_TIMEOUT => self::DNS_CACHE_TTL_SECONDS,
                CURLOPT_ENCODING => "",
            ];

            if (defined("CURL_IPRESOLVE_V4")) {
                $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }

            if (defined("CURL_HTTP_VERSION_2TLS")) {
                $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2TLS;
            }

            if (!is_null($body)) {
                $options[CURLOPT_POSTFIELDS] = $body;
            } else {
                $options[CURLOPT_POSTFIELDS] = null;
                if ($method === "GET") {
                    $options[CURLOPT_HTTPGET] = true;
                }
            }

            curl_setopt_array($handle, $options);

            $content = curl_exec($handle);
            if ($content === false) {
                $errorMessage = curl_error($handle);
                $errorCode = curl_errno($handle);
                throw new UnknownError("cURL error ({$errorCode}): {$errorMessage}");
            }

            $status = intval(curl_getinfo($handle, CURLINFO_RESPONSE_CODE));

            return new Response($status, strval($content));
        } catch (UnknownError $e) {
            throw $e;
        } catch (Exception $e) {
            throw new UnknownError(strval($e));
        } finally {
            self::releaseHandle($origin, $handle);
        }
    }

    private static function acquireHandle(string $origin): CurlHandle
    {
        if (!empty(self::$handlePool[$origin])) {
            $handle = array_pop(self::$handlePool[$origin]);
            if ($handle instanceof CurlHandle) {
                return $handle;
            }
        }

        $handle = curl_init();
        if ($handle === false) {
            throw new UnknownError("Unable to initialize cURL handle");
        }

        return $handle;
    }

    private static function releaseHandle(string $origin, CurlHandle $handle): void
    {
        if (!isset(self::$handlePool[$origin])) {
            self::$handlePool[$origin] = [];
        }

        if (count(self::$handlePool[$origin]) < self::MAX_POOL_SIZE_PER_HOST) {
            self::$handlePool[$origin][] = $handle;
        }
    }

    private static function encodeQuery($query): string
    {
        $query = API::castJsonToApiFormat($query);
        $queryArray = [];
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                $value = implode(",", $value);
            } elseif (is_iterable($value)) {
                $stringValue = [];
                foreach ($value as $v) {
                    $stringValue[] = strval($v);
                }
                $value = implode(",", $stringValue);
            } elseif (is_bool($value)) {
                $value = $value ? "true" : "false";
            }
            $queryArray[$key] = $value;
        }

        if (count($queryArray) > 0) {
            return "?" . http_build_query($queryArray, "", "&", PHP_QUERY_RFC3986);
        }

        return "";
    }

    private static function _authenticationHeaders($user, $body): array
    {
        if ($user instanceof PublicUser) {
            return [];
        }

        $accessTime = strval(time());
        $message = $user->accessId() . ":" . $accessTime . ":" . $body;
        $signature = Ecdsa::sign($message, $user->privateKey())->toBase64();

        return [
            "Access-Id" => $user->accessId(),
            "Access-Time" => $accessTime,
            "Access-Signature" => $signature,
        ];
    }
}
