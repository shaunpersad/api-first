<?php


namespace Api\V2;


class CatsResource extends AbstractEntityResource {


    protected $with = array('user');


    /**
     * @param AbstractResource $cats
     * @return array
     */
    public static function endpointFilters(AbstractResource $cats = null) {

        return array(
            'all' => Api::FILTER_ADMINS_ONLY, // /cats
            'create' => Api::FILTER_ADMINS_ONLY, // /cats/create
            'get' => Api::FILTER_OWNER_OR_ADMINS, // /cats/{cat_id}
        );
    }

    /**
     * @return mixed
     */
    public function query() {

        return Cat::query();
    }

    protected function all($params = array()) {

        $defaults = array(
            'with' => $with = null,
            'order_by' => $order_by = 'users.created_at',
            'order_dir' => $order_dir = 'DESC',
            'page' => $page = 1,
            'per_page' => $per_page = 30
        );

        $params = $this->validateParams($defaults, $params);

        extract($params);

        $query = $this->query();

        $collection = $this->paginate($query, 'cats.id', $page, $per_page, $order_by, $order_dir);

        return $collection;

    }

    protected function create($params = array()) {

        $defaults = array(
            'name' => $name = null,
            'color' => $color = null
        );

        $rules = array(
            'name' => array(
                'required'
            ),
            'color' => array(
                'required',
                'in:brown,black'
            )
        );

        $params = $this->validateParams($defaults, $params, $rules);

        extract($params);

        $cat = new Cat();
        $cat->name = $name;
        $cat->color = $color;
        $cat->save();

        $this->entity = $cat;

        return $this->entity();

    }

} 