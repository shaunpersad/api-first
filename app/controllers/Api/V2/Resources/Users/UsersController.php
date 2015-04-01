<?php


namespace Api\V2;


class UsersController extends AbstractApiController {

    public function getIndex(User $user = null) {

        if (!empty($user)) {

            /**
             * Mirrors /users/{user_id}
             */
            return $this->api->users($user)->get();
        }
        /**
         * Mirrors /users
         */
        return $this->api->users()->all();
    }

    public function postCreate() {

        /**
         * Mirrors /users/{user_id}/create
         */
        return $this->api->users()->create(Input::all());
    }

} 