<?php

namespace StarkInfra;
use StarkInfra\Utils\Rest;
use StarkCore\Utils\Checks;
use StarkCore\Utils\Resource;
use StarkCore\Utils\StarkDate;

class TrustedIndividualAccountRequest extends Resource
{
    public $taxId;
    public $name;
    public $address;
    public $income;
    public $risk;
    public $birthDate;
    public $tags;
    public $biometricHash;
    public $biometricProvider;
    public $status;
    public $accountType;
    public $trustedPartnerId;
    public $created;
    public $updated;

    /**
    # TrustedIndividualAccountRequest object

    Opens an account for an individual identified by CPF, skipping identity webview.
    Approvals return synchronously with status "approved".

    ## Parameters (required):
        - taxId [string]: Brazilian CPF. ex: "012.345.678-90"
        - name [string]: full legal name of the individual (at least 2 words). ex: "Jamie Lannister"
        - address [array]: structured address with required keys "street", "number", "neighborhood", "city", "state", "zipCode" and optional "complement".
        - income [integer]: monthly income in cents (integer >= 0). ex: 500000
        - risk [integer]: declared risk level, from 1 to 5. ex: 2

    ## Parameters (optional):
        - birthDate [string, default null]: ISO date (YYYY-MM-DD). ex: "1990-03-01"
        - tags [array of strings, default null]: list of strings for reference. ex: ["client/1234"]
        - biometricHash [string, default null]: biometric reference hash/id (up to 256 chars). ex: "a3f1c9e2d4b6"
        - biometricProvider [string, default null]: biometric provider name (up to 64 chars). ex: "AuthDesk"

    ## Attributes (return-only):
        - id [string]: unique id returned upon creation. ex: "5717189529174016"
        - status [string]: always "approved".
        - accountType [string]: always "individual".
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
        $this->income = Checks::checkParam($params, "income");
        $this->risk = Checks::checkParam($params, "risk");
        $this->birthDate = Checks::checkParam($params, "birthDate");
        $this->tags = Checks::checkParam($params, "tags");
        $this->biometricHash = Checks::checkParam($params, "biometricHash");
        $this->biometricProvider = Checks::checkParam($params, "biometricProvider");
        $this->status = Checks::checkParam($params, "status");
        $this->accountType = Checks::checkParam($params, "accountType");
        $this->trustedPartnerId = Checks::checkParam($params, "trustedPartnerId");
        $this->created = Checks::checkDateTime(Checks::checkParam($params, "created"));
        $this->updated = Checks::checkDateTime(Checks::checkParam($params, "updated"));

        Checks::checkParams($params);
    }

    /**
    # Create TrustedIndividualAccountRequests

    Send an array of TrustedIndividualAccountRequest objects for synchronous creation and approval.

    ## Parameters (required):
        - requests [array of TrustedIndividualAccountRequest objects]: array of requests to be created.

    ## Parameters (optional):
        - user [Organization/Project object, default null]: Organization or Project object.

    ## Return:
        - array of TrustedIndividualAccountRequest objects with updated attributes
     */
    public static function create($requests, $user = null)
    {
        return Rest::post($user, TrustedIndividualAccountRequest::resource(), $requests);
    }

    /**
    # Retrieve a specific TrustedIndividualAccountRequest

    ## Parameters (required):
        - id [string]: object unique id.

    ## Parameters (optional):
        - user [Organization/Project object, default null]: Organization or Project object.

    ## Return:
        - TrustedIndividualAccountRequest object
     */
    public static function get($id, $user = null)
    {
        return Rest::getId($user, TrustedIndividualAccountRequest::resource(), $id);
    }

    /**
    # Retrieve TrustedIndividualAccountRequests

    ## Parameters (optional):
        - limit [integer, default null]: maximum number of objects to retrieve.
        - after [Date or string, default null]: date filter for creation date.
        - before [Date or string, default null]: date filter for creation date.
        - status [string, default null]: filter for status.
        - tags [array of strings, default null]: tags filter.
        - ids [array of strings, default null]: ids filter.
        - user [Organization/Project object, default null]: Organization or Project object.

    ## Return:
        - enumerator of TrustedIndividualAccountRequest objects
     */
    public static function query($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));
        return Rest::getList($user, TrustedIndividualAccountRequest::resource(), $options);
    }

    /**
    # Retrieve paged TrustedIndividualAccountRequests

    ## Return:
        - list of TrustedIndividualAccountRequest objects
        - cursor to retrieve the next page
     */
    public static function page($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));
        return Rest::getPage($user, TrustedIndividualAccountRequest::resource(), $options);
    }

    private static function resource()
    {
        $request = function ($array) {
            return new TrustedIndividualAccountRequest($array);
        };
        return [
            "name" => "TrustedIndividualAccountRequest",
            "maker" => $request,
        ];
    }
}
