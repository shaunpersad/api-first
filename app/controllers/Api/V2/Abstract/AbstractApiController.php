<?php


namespace Api\V2;


use BaseController;
use Config;

abstract class AbstractApiController extends BaseController {

    protected $api;

    public function __construct(Api $api) {

        Config::set('session.driver', 'array'); // no cookies plz

        $this->api = $api;
    }

    public function missingMethod($parameters = array()) {

        throw $this->api->exception(Api::EXCEPTION_ENDPOINT_FOR_METHOD_NOT_FOUND);
    }
}