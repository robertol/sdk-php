<?php

namespace StarkInfra\BusinessAccountAttachment;
use StarkCore\Utils\API;
use StarkInfra\Utils\Rest;
use StarkCore\Utils\Checks;
use StarkCore\Utils\Resource;
use StarkCore\Utils\StarkDate;
use StarkInfra\BusinessAccountAttachment;

class Log extends Resource
{
    public $attachment;
    public $type;
    public $errors;
    public $created;

    function __construct(array $params)
    {
        parent::__construct($params);

        $this->attachment = Checks::checkParam($params, "attachment");
        $this->type = Checks::checkParam($params, "type");
        $this->errors = Checks::checkParam($params, "errors");
        $this->created = Checks::checkDateTime(Checks::checkParam($params, "created"));

        Checks::checkParams($params);
    }

    public static function get($id, $user = null)
    {
        return Rest::getId($user, Log::resource(), $id);
    }

    public static function query($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));

        return Rest::getList($user, Log::resource(), $options);
    }

    public static function page($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));

        return Rest::getPage($user, Log::resource(), $options);
    }

    private static function resource()
    {
        $attachmentLog = function ($array) {
            $attachment = function ($array) {
                return new BusinessAccountAttachment($array);
            };
            $array["attachment"] = API::fromApiJson($attachment, $array["attachment"]);
            return new Log($array);
        };
        return [
            "name" => "BusinessAccountAttachmentLog",
            "maker" => $attachmentLog,
        ];
    }
}
