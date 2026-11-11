<?php

namespace Test\TrustedAccountRequest;

use Exception;
use StarkInfra\TrustedIndividualAccountRequest;
use StarkInfra\TrustedBusinessAccountRequest;
use StarkInfra\TrustedBusinessAccountRequest\Owner;
use StarkInfra\BusinessAccountAttachment;
use StarkInfra\Utils\API;

class TestTrustedAccountRequest
{
    public function testSerialization()
    {
        $pf = new TrustedIndividualAccountRequest([
            "taxId" => "012.345.678-90",
            "name" => "Jamie Lannister",
            "income" => 500000,
            "risk" => 2,
            "address" => [
                "street" => "Faria Lima Avenue",
                "number" => "1844",
                "neighborhood" => "Itaim Bibi",
                "city" => "Sao Paulo",
                "state" => "SP",
                "zipCode" => "01500-000",
            ],
            "biometricHash" => "a3f1c9e2d4b6",
            "biometricProvider" => "AuthDesk",
        ]);

        $pfJson = API::apiJson($pf);
        if ($pfJson["taxId"] !== "012.345.678-90" || $pfJson["risk"] !== 2) {
            throw new Exception("PF serialization failed");
        }

        $owner = new Owner([
            "taxId" => "012.345.678-90",
            "name" => "Jamie Lannister",
            "role" => "partner",
            "risk" => 2,
            "biometricHash" => "a3f1c9e2d4b6",
            "biometricProvider" => "AuthDesk",
        ]);

        $pj = new TrustedBusinessAccountRequest([
            "taxId" => "20.018.183/0001-80",
            "name" => "Casterly Rock Mining Co.",
            "revenue" => 10000000,
            "risk" => 3,
            "address" => [
                "street" => "Faria Lima Avenue",
                "number" => "1844",
                "neighborhood" => "Itaim Bibi",
                "city" => "Sao Paulo",
                "state" => "SP",
                "zipCode" => "01500-000",
            ],
            "owners" => [$owner],
        ]);

        $pjJson = API::apiJson($pj);
        if ($pjJson["taxId"] !== "20.018.183/0001-80" || $pjJson["risk"] !== 3) {
            throw new Exception("PJ serialization failed");
        }
        if (isset($pjJson["biometricHash"])) {
            throw new Exception("PJ root must not contain biometricHash");
        }
        if ($pjJson["owners"][0]["risk"] !== 2) {
            throw new Exception("Owner risk serialization failed");
        }

        $attachment = new BusinessAccountAttachment([
            "accountRequestId" => "5650123456789012",
            "type" => "article-of-incorporation",
            "content" => "data:application/pdf;base64,JVBERi0xLjQKJ...",
        ]);

        $attachJson = API::apiJson($attachment);
        if ($attachJson["type"] !== "article-of-incorporation") {
            throw new Exception("Attachment serialization failed");
        }
    }
}
