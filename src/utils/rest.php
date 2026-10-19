<?php

namespace StarkInfra\Utils;

use StarkInfra\Settings;
use StarkCore\Utils\Checks;
use StarkCore\Utils\StarkHost;


class Rest
{
    private const apiVersion = "v2";
    private const sdkVersion = "0.17.0";
    private const host = StarkHost::infra;

    public static function getPage($user, $resource, array $query = [])
    {
        $json = Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "GET",
            API::endpoint($resource["name"]),
            null,
            $query,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->json();

        $entities = [];
        foreach ($json[API::lastNamePlural($resource["name"])] as $entity) {
            $entities[] = API::fromApiJson($resource["maker"], $entity);
        }

        return [$entities, $json["cursor"]];
    }

    public static function getList($user, $resource, array $query = [])
    {
        $limit = Checks::checkParam($query, "limit");
        $query["limit"] = is_null($limit) ? null : min($limit, 100);

        while (true) {
            $json = Request::fetch(
                self::getHost(),
                self::getSdkVersion(),
                Settings::getUser($user),
                "GET",
                API::endpoint($resource["name"]),
                null,
                $query,
                self::getApiVersion(),
                Settings::getLanguage(),
                Settings::getTimeout()
            )->json();

            $entities = $json[API::lastNamePlural($resource["name"])];

            foreach ($entities as $entity) {
                yield API::fromApiJson($resource["maker"], $entity);
            }

            if (!is_null($limit)) {
                $limit -= 100;
                $query["limit"] = min($limit, 100);
            }

            if (!array_key_exists("cursor", $json)) {
                break;
            }

            $cursor = $json["cursor"];
            $query["cursor"] = $cursor;
            if (empty($cursor) || is_null($cursor) || (!is_null($limit) && $limit <= 0)) {
                break;
            }
        }
    }

    public static function getId($user, $resource, $id, $query = [])
    {
        $id = Checks::checkId($id);
        $json = Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "GET",
            API::endpoint($resource["name"]) . "/" . $id,
            null,
            $query,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->json();

        $entity = $json[API::lastName($resource["name"])];

        return API::fromApiJson($resource["maker"], $entity);
    }

    public static function getContent($user, $resource, $id, $subresourceName, $options = null)
    {
        $id = Checks::checkId($id);
        $options = API::castJsonToApiFormat($options);
        $path = API::endpoint($resource["name"]) . "/" . $id . "/" . $subresourceName;

        return Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "GET",
            $path,
            null,
            $options,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->content;
    }

    public static function getSubresource($user, $resource, $id, $subresource, $options = null)
    {
        $id = Checks::checkId($id);
        $options = API::castJsonToApiFormat($options);
        $path = API::endpoint($resource["name"]) . "/" . $id . "/" . API::endpoint($subresource["name"]);
        $json = Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "GET",
            $path,
            null,
            $options,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->json();

        $entity = $json[API::lastName($subresource["name"])];

        return API::fromApiJson($subresource["maker"], $entity);
    }

    public static function postSubResource($user, $resource, $id, $subresource, $entity, $options = null)
    {
        $id = Checks::checkId($id);
        $payload = API::apiJson($entity, $subresource["name"]);
        $json = Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "POST",
            API::endpoint($resource["name"]) . "/" . $id . "/" . API::endpoint($subresource["name"]),
            $payload,
            null,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->json();

        $entityJson = $json[API::lastName($subresource["name"])];

        return API::fromApiJson($subresource["maker"], $entityJson);
    }

    public static function post($user, $resource, $entities, $query = null)
    {
        $entitiesJson = [];
        foreach ($entities as $entity) {
            $entitiesJson[] = API::apiJson($entity, $resource["name"]);
        }
        $payload = [
            API::lastNamePlural($resource["name"]) => $entitiesJson,
        ];

        $json = Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "POST",
            API::endpoint($resource["name"]),
            $payload,
            $query,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->json();

        $retrievedEntities = [];
        foreach ($json[API::lastNamePlural($resource["name"])] as $entity) {
            $retrievedEntities[] = API::fromApiJson($resource["maker"], $entity);
        }

        return $retrievedEntities;
    }

    public static function postSingle($user, $resource, $entity)
    {
        $payload = API::apiJson($entity);
        $json = Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "POST",
            API::endpoint($resource["name"]),
            $payload,
            null,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->json();

        $entityJson = $json[API::lastName($resource["name"])];

        return API::fromApiJson($resource["maker"], $entityJson);
    }

    public static function deleteId($user, $resource, $id)
    {
        $id = Checks::checkId($id);
        $json = Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "DELETE",
            API::endpoint($resource["name"]) . "/" . $id,
            null,
            null,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->json();

        $entity = $json[API::lastName($resource["name"])];

        return API::fromApiJson($resource["maker"], $entity);
    }

    public static function patchId($user, $resource, $id, $payload = [])
    {
        $id = Checks::checkId($id);
        $json = Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "PATCH",
            API::endpoint($resource["name"]) . "/" . $id,
            API::castJsonToApiFormat($payload, $resource["name"]),
            null,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout()
        )->json();

        $entity = $json[API::lastName($resource["name"])];

        return API::fromApiJson($resource["maker"], $entity);
    }

    public static function getSdkVersion()
    {
        return self::sdkVersion;
    }

    public static function getApiVersion()
    {
        return self::apiVersion;
    }

    public static function getHost()
    {
        return self::host;
    }

    public static function postRaw($user, $path, $payload, $prefix = null, $throwError = true, $query = null)
    {
        return Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "POST",
            $path,
            $payload,
            $query,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout(),
            $prefix,
            $throwError
        );
    }

    public static function getRaw($user, $path, $query = null, $prefix = null, $throwError = true, $payload = null)
    {
        return Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "GET",
            $path,
            $payload,
            $query,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout(),
            $prefix,
            $throwError
        );
    }

    public static function patchRaw($user, $path, $payload, $prefix = null, $throwError = true, $query = null)
    {
        return Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "PATCH",
            $path,
            $payload,
            $query,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout(),
            $prefix,
            $throwError
        );
    }

    public static function putRaw($user, $path, $payload, $prefix = null, $throwError = true, $query = null)
    {
        return Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "PUT",
            $path,
            $payload,
            $query,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout(),
            $prefix,
            $throwError
        );
    }

    public static function deleteRaw($user, $path, $payload, $prefix = null, $throwError = true, $query = null)
    {
        return Request::fetch(
            self::getHost(),
            self::getSdkVersion(),
            Settings::getUser($user),
            "DELETE",
            $path,
            $payload,
            $query,
            self::getApiVersion(),
            Settings::getLanguage(),
            Settings::getTimeout(),
            $prefix,
            $throwError
        );
    }
}
