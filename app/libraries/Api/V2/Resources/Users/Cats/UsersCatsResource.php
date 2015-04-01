<?php


namespace Api\V2;


class UsersCatsResource extends CatsResource {

    protected $with = array();


    /**
     * @param AbstractResource $users_cats
     * @return array
     */
    public static function endpointFilters(AbstractResource $users_cats = null) {

        return array(
            'all' => Api::FILTER_ADMINS_ONLY, // /users/{user_id}/cats
            'create' => Api::FILTER_ADMINS_ONLY, // /users/{user_id}/cats/create
            'get' => Api::FILTER_OWNER_OR_ADMINS, // /users/{user_id}/cats/{cat_id}
        );
    }

    public function query() {

        $user = $this->parent_resource->entity();

        return $user->cats();
    }

    protected function create($params = array()) {

        $user = $this->parent_resource->entity();

        $cat = parent::create($params);
        $cat->user->associate($user);

        return $cat;
    }

} 