<?php

namespace StarkInfra\TrustedBusinessAccountRequest;
use StarkCore\Utils\Checks;
use StarkCore\Utils\SubResource;

class Owner extends SubResource
{
    public $taxId;
    public $name;
    public $role;
    public $risk;
    public $biometricHash;
    public $biometricProvider;

    /**
    # TrustedBusinessAccountRequest\Owner object

    Partner or legal representative of a company opening a trusted business account.

    ## Parameters (required):
        - taxId [string]: owner CPF. ex: "012.345.678-90"
        - name [string]: owner full legal name. ex: "Jamie Lannister"
        - role [string]: "partner" or "representative".
        - risk [integer]: declared risk level, from 1 to 5. ex: 2

    ## Parameters (optional):
        - biometricHash [string, default null]: biometric reference hash/id (up to 256 chars). ex: "a3f1c9e2d4b6"
        - biometricProvider [string, default null]: biometric provider name (up to 64 chars). ex: "AuthDesk"
     */
    function __construct(array $params)
    {
        $this->taxId = Checks::checkParam($params, "taxId");
        $this->name = Checks::checkParam($params, "name");
        $this->role = Checks::checkParam($params, "role");
        $this->risk = Checks::checkParam($params, "risk");
        $this->biometricHash = Checks::checkParam($params, "biometricHash");
        $this->biometricProvider = Checks::checkParam($params, "biometricProvider");

        Checks::checkParams($params);
    }
}
