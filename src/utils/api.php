<?php

namespace StarkInfra\Utils;

use DateTime;
use DateInterval;
use DateTimeZone;
use StarkCore\Utils\Resource;
use StarkCore\Utils\SubResource;
use StarkCore\Utils\StarkDate;
use StarkCore\Utils\StarkDateTime;
use StarkCore\Utils\StringCase;


class API
{
    public static function apiJson($entity, $resourceName = null): array
    {
        if (!is_array($entity)) {
            $entity = $entity->__toArray();
        }

        return self::castJsonToApiFormat($entity, $resourceName);
    }

    /**
     * Converte atributos para o formato esperado pela API Stark sem executar
     * utf8_decode/utf8_encode (depreciados no PHP 8.2+ e que corrompiam caracteres fora do Latin-1).
     */
    public static function castJsonToApiFormat($json, $resourceName = null): array
    {
        $clean = [];
        if (is_null($json)) {
            return $clean;
        }

        foreach ($json as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            if (is_string($value)) {
                $clean[$key] = $value;
                continue;
            }
            if ($value instanceof DateTime) {
                $clean[$key] = self::convertDateTime($value);
                continue;
            }
            if ($value instanceof StarkDateTime || $value instanceof StarkDate) {
                $clean[$key] = $value->__toString();
                continue;
            }
            if ($value instanceof DateInterval) {
                $clean[$key] = self::convertDateInterval($value);
                continue;
            }
            if (is_array($value)) {
                $clean[$key] = self::castJsonToApiFormat($value, $resourceName);
                continue;
            }
            if ($value instanceof SubResource || $value instanceof Resource) {
                $clean[$key] = self::castJsonToApiFormat(get_object_vars($value), $resourceName);
                continue;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }

    private static function convertDateTime(DateTime $value): string
    {
        if ($value->format("H:i:s.u") == "00:00:00.000000") {
            return $value->format("Y-m-d");
        }
        $value->setTimezone(new DateTimeZone("UTC"));

        return $value->format("Y-m-d\TH:i:s.u") . "+00:00";
    }

    private static function convertDateInterval(DateInterval $value): int
    {
        $total = $value->y;
        $total = ($total * 12) + ($value->m);
        $total = ($total * 30) + ($value->d);
        $total = ($total * 24) + ($value->h);
        $total = ($total * 60) + ($value->i);
        $total = ($total * 60) + ($value->s);

        return $total;
    }

    public static function fromApiJson($resourceMaker, $json)
    {
        $json["checkParams"] = false;

        return $resourceMaker($json);
    }

    public static function endpoint($resourceName): string
    {
        $targets = ["-log", "-attempt"];
        $replacements = ["/log", "/attempt"];

        return str_replace($targets, $replacements, StringCase::camelToKebab($resourceName));
    }

    public static function lastName($resourceName): string
    {
        $parts = explode("-", StringCase::camelToKebab($resourceName));

        return end($parts);
    }

    public static function lastNamePlural($resourceName): string
    {
        $lastName = self::lastName($resourceName);
        if (substr($lastName, -1) === "s") {
            return $lastName;
        }
        if (substr($lastName, -1) === "y" && substr($lastName, -2) !== "ey") {
            return substr($lastName, 0, -1) . "ies";
        }
        if (preg_match('/(ch|sh|x)$/', $lastName)) {
            return $lastName . "es";
        }

        return $lastName . "s";
    }
}
