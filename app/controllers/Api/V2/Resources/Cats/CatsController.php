<?php


namespace Api\V2;


class CatsController extends AbstractApiController {

    public function getIndex(Cat $cat = null) {

        if (!empty($cat)) {

            return $this->api->cats($cat)->get();
        }
        return $this->api->cats()->all();
    }

    public function postCreate() {

        return $this->api->cats()->create(Input::all());
    }
} 