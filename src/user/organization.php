<?php

namespace StarkInfra;

use EllipticCurve\PrivateKey;
use StarkCore\Utils\Checks;


class Organization extends \StarkCore\Organization
{
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
        $this->cachedPrivateKey = Project::resolvePrivateKey($pem);
        $this->pem = $pem;
        $this->environment = Checks::checkEnvironment(Checks::checkParam($params, "environment"));
        $this->workspaceId = Checks::checkParam($params, "workspaceId");

        Checks::checkParams($params);
    }

    public function privateKey()
    {
        if ($this->cachedPrivateKey instanceof PrivateKey) {
            return $this->cachedPrivateKey;
        }

        $this->cachedPrivateKey = Project::resolvePrivateKey($this->pem);

        return $this->cachedPrivateKey;
    }

    public static function replace($organization, $workspaceId)
    {
        return new Organization([
            "id" => $organization->id,
            "environment" => $organization->environment,
            "privateKey" => $organization->pem,
            "workspaceId" => $workspaceId,
        ]);
    }
}
