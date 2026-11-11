<?php

namespace StarkInfra;
use StarkInfra\Utils\Rest;
use StarkCore\Utils\Checks;
use StarkCore\Utils\Resource;
use StarkCore\Utils\StarkDate;

class BusinessAccountAttachment extends Resource
{
    public $type;
    public $content;
    public $contentType;
    public $accountRequestId;
    public $tags;
    public $status;
    public $created;

    /**
    # BusinessAccountAttachment object

    Supporting document attached to a TrustedBusinessAccountRequest or BusinessAccountRequest.
    Accepted types: "article-of-incorporation", "general-meeting-minute".

    ## Parameters (required):
        - type [string]: type of document. Options: "article-of-incorporation", "general-meeting-minute"
        - content [string]: raw bytes or base64 data-URI string.
        - accountRequestId [string]: id of the parent BusinessAccountRequest / TrustedBusinessAccountRequest.

    ## Parameters (optional):
        - contentType [string, default null]: content MIME type. ex: "application/pdf", "image/jpeg"
        - tags [array of strings, default null]: list of strings for reference.

    ## Attributes (return-only):
        - id [string]: unique id returned when created.
        - status [string]: "created", "processing", "success", "failed", "canceled", "deleted".
        - created [DateTime]: creation datetime.
     */
    function __construct(array $params)
    {
        parent::__construct($params);

        $this->type = Checks::checkParam($params, "type");
        $this->content = Checks::checkParam($params, "content");
        $this->contentType = Checks::checkParam($params, "contentType");
        $this->accountRequestId = Checks::checkParam($params, "accountRequestId");
        $this->tags = Checks::checkParam($params, "tags");
        $this->status = Checks::checkParam($params, "status");
        $this->created = Checks::checkDateTime(Checks::checkParam($params, "created"));

        Checks::checkParams($params);
    }

    /**
    # Create BusinessAccountAttachments
     */
    public static function create($attachments, $user = null)
    {
        foreach ($attachments as $attachment) {
            if ($attachment->contentType != null) {
                $attachment->content = "data:" . $attachment->contentType . ";base64," . base64_encode($attachment->content);
                $attachment->contentType = null;
            }
        }
        return Rest::post($user, BusinessAccountAttachment::resource(), $attachments);
    }

    /**
    # Retrieve a specific BusinessAccountAttachment
     */
    public static function get($id, $user = null)
    {
        return Rest::getId($user, BusinessAccountAttachment::resource(), $id);
    }

    /**
    # Retrieve BusinessAccountAttachments
     */
    public static function query($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));
        return Rest::getList($user, BusinessAccountAttachment::resource(), $options);
    }

    /**
    # Retrieve paged BusinessAccountAttachments
     */
    public static function page($options = [], $user = null)
    {
        $options["after"] = new StarkDate(Checks::checkParam($options, "after"));
        $options["before"] = new StarkDate(Checks::checkParam($options, "before"));
        return Rest::getPage($user, BusinessAccountAttachment::resource(), $options);
    }

    /**
    # Cancel a BusinessAccountAttachment entity
     */
    public static function cancel($id, $user = null)
    {
        return Rest::deleteId($user, BusinessAccountAttachment::resource(), $id);
    }

    private static function resource()
    {
        $attachment = function ($array) {
            return new BusinessAccountAttachment($array);
        };
        return [
            "name" => "BusinessAccountAttachment",
            "maker" => $attachment,
        ];
    }
}
