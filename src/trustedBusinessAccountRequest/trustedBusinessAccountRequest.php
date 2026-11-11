<?php

namespace StarkInfra;
use StarkCore\Utils\API;
use StarkInfra\Utils\Rest;
use StarkCore\Utils\Checks;
use StarkCore\Utils\Resource;
use StarkCore\Utils\StarkDate;
use StarkInfra\TrustedBusinessAccountRequest\Owner;

class TrustedBusinessAccountRequest extends Resource
{
    public $taxId;
    public $name;
    public $address;
    public $revenue;
    public $risk;
    public $owners;
    public $tags;
    public $status;
    public $accountType;
    public $trustedPartnerId;
    public $created;
    public $updated;

    /**
    # TrustedBusinessAccountRequest object

    Opens an account for a legal entity identified by CNPJ, skipping identity webview.
    Approvals return synchronously with status "approved".

    ## Parameters (required):
        - taxId [string]: Brazilian CNPJ. ex: "20.018.183/0001-80"
        - name [string]: business legal name. ex: "Casterly Rock Mining Co."
        - address [array]: structured address with required keys "street", "number", "neighborhood", "city", "state", "zipCode" and optional "complement".
        - revenue [integer]: monthly revenue in cents (integer >= 0). ex: 10000000
        - risk [integer]: declared risk level of the business, from 1 to 5. ex: 3
        - owners [array of Owner objects or arrays]: list of 1 to 10 company owners/representatives with unique CPFs.

    ## Parameters (optional):
        - tags [array of strings, default null]: list of strings for reference. ex: ["client/5678"]

    ## Attributes (return-only):
        - id [string]: unique id returned upon creation. ex: "5650123456789012"
        - status [string]: always "approved".
        - accountType [string]: always "business".
        - trustedPartnerId [string]: id of the trusted partner enrollment used.
        - created [DateTime]: creation datetime.
        - updated [DateTime]: latest update datetime.
     */
    function __construct(array $params)
    {
        parent::__construct($params);

        $this->taxId = Checks::checkParam($params, "taxId");
        $this->name = Checks::checkParam($params, "name");
        $this->address = Checks::checkParam($params, "address");
        $this->revenue = Checks::checkParam($params, "revenue");
        $this->risk = Checks::checkParam($params, "risk");
        $this->owners = Checks::checkParam($params, "owners");
        $this->tags = Checks::checkParam($params, "tags");
        $this->status = Checks::checkParam($params, "status");
        $this->accountType = Checks::checkParam($params, "accountType");
        $this->trustedPartnerId = Checks::checkParam($params, "trustedPartnerId");
        $this->created = Checks::checkDateTime(Checks::checkParam($params, "created"));
        $this->updated = Checks::checkDateTime(Checks::checkParam($params, "updated"));

        Checks::checkParams($params);
    }

    /**
    # Create TrustedBusinessAccountRequests

    Send an array of TrustedBusinessAccountRequest objects for synchronous creation and approval.
     */
    public static function create($requests, $user = null)
    {
        return Rest::post($user, TrustedBusinessAccountRequest::resource(), $requests);
    }

    /**
    # Retrieve a specific TrustedBusinessAccountRequest
     */
    public static function get($id, $user = null)
    {
        return Rest::getId($user, TrustedBusinessAccountRequest::resource(), $id);
    }

    /**
    # Retrieve TrustedBusinessAccountRequests
     */
    public static function query($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));
        return Rest::getList($user, TrustedBusinessAccountRequest::resource(), $options);
    }

    /**
    # Retrieve paged TrustedBusinessAccountRequests
     */
    public static function page($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));
        return Rest::getPage($user, TrustedBusinessAccountRequest::resource(), $options);
    }

    private static function resource()
    {
        $request = function ($array) {
            $ownerMaker = function ($owner) {
                return new Owner($owner);
            };
            if (isset($array["owners"]) && is_array($array["owners"])) {
                $array["owners"] = array_map(function ($owner) use ($ownerMaker) {
                    return $owner instanceof Owner ? $owner : API::fromApiJson($ownerMaker, $owner);
                }, $array["owners"]);
            }
            return new TrustedBusinessAccountRequest($array);
        };
        return [
            "name" => "TrustedBusinessAccountRequest",
            "maker" => $request,
        ];
    }
}
