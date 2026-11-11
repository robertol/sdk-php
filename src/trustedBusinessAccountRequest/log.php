<?php

namespace StarkInfra\TrustedBusinessAccountRequest;
use StarkCore\Utils\API;
use StarkInfra\Utils\Rest;
use StarkCore\Utils\Checks;
use StarkCore\Utils\Resource;
use StarkCore\Utils\StarkDate;
use StarkInfra\TrustedBusinessAccountRequest;

class Log extends Resource
{
    public $request;
    public $type;
    public $errors;
    public $created;

    /**
    # TrustedBusinessAccountRequest\Log object

    Every time a TrustedBusinessAccountRequest is updated/approved, a corresponding Log is generated.

    ## Attributes (return-only):
        - id [string]: unique id returned when the log is created.
        - request [TrustedBusinessAccountRequest]: request entity to which the log refers.
        - errors [array of strings]: array of errors linked to this event.
        - type [string]: type of event (e.g. "trustedApproval").
        - created [DateTime]: creation datetime for the log.
     */
    function __construct(array $params)
    {
        parent::__construct($params);

        $this->request = Checks::checkParam($params, "request");
        $this->type = Checks::checkParam($params, "type");
        $this->errors = Checks::checkParam($params, "errors");
        $this->created = Checks::checkDateTime(Checks::checkParam($params, "created"));

        Checks::checkParams($params);
    }

    /**
    # Retrieve a specific TrustedBusinessAccountRequest\Log
     */
    public static function get($id, $user = null)
    {
        return Rest::getId($user, Log::resource(), $id);
    }

    /**
    # Retrieve TrustedBusinessAccountRequest\Logs
     */
    public static function query($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));

        return Rest::getList($user, Log::resource(), $options);
    }

    /**
    # Retrieve paged TrustedBusinessAccountRequest\Logs
     */
    public static function page($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));

        return Rest::getPage($user, Log::resource(), $options);
    }

    private static function resource()
    {
        $requestLog = function ($array) {
            $trustedBusinessAccountRequest = function ($array) {
                return new TrustedBusinessAccountRequest($array);
            };
            $array["request"] = API::fromApiJson($trustedBusinessAccountRequest, $array["request"]);
            return new Log($array);
        };
        return [
            "name" => "TrustedBusinessAccountRequestLog",
            "maker" => $requestLog,
        ];
    }
}
