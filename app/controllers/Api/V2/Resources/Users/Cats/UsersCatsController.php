<?php


namespace Api\V2;


class UsersCatsController extends AbstractApiController {

    public function getIndex(User $user, Cat $cat = null) {

        if (!empty($cat)) {

            /**
             * Mirrors /users/{user_id}/cats/{cat_id}
             */
            return $this->api->users($user)->cats($cat)->get();
        }
        /**
         * Mirrors /users/{user_id}/cats
         */
        return $this->api->users()->cats()->all();
    }

    public function postCreate(User $user) {

        /**
         * Mirrors /users/{user_id}/cats/create
         */
        return $this->api->users($user)->cats()->create(Input::all());
    }
} 