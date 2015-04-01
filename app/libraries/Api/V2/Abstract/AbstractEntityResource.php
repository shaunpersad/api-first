<?php


namespace Api\V2;



abstract class AbstractEntityResource extends AbstractResource {


    /**
     * This is the actual entity pulled from the database.
     *
     * e.g. for /users/{user_id},
     * the entity would be the User object.
     *
     * @var ApiEloquent|null
     */
    protected $entity;

    /**
     * The public entity relations an API user can request via the "with" param.
     * You may use this or the private_entity_relations attribute, or both.
     *
     * @var array
     */
    protected $public_entity_relations = array();

    /**
     * The private entity relations an API user has no access to via the "with" param.
     * You may use this or the public_entity_relations attribute, or both.
     *
     * @var array
     */
    protected $private_entity_relations = array();

    /**
     * The relations to load with every entity returned.
     *
     * @var array
     */
    protected $with = array();

    public function __construct(Api $api, ApiEloquent $entity = null, AbstractEntityResource $parent_resource = null) {

        parent::__construct($api, $parent_resource);

        $this->entity = $entity;
        $resource = $this;
        Validator::extend('with', function($attribute, $value, $parameters) use($resource) {

            $resource->validateRequestedEntityRelations($value);
            return true;
        });

    }

    /**
     * @return mixed
     */
    abstract public function query();

    abstract protected function all($params = array());

    protected function defaultAction($params = array()) {

        if (!empty($this->entity)) {
            return $this->get($params);
        }
        return $this->all($params = array());
    }

    protected function get($params = array()) {

        $defaults = array(

            'with' => $with = null
        );

        $params = $this->validateParams($defaults, $params);

        extract($params);

        return $this->entity();
    }

    protected function remove($params = array()) {

        $entity = $this->entity();

        if ($entity->delete()) {

            return $entity;
        }
        throw $this->api->exception();
    }

    /**
     * @return mixed
     * @throws \Api\Exceptions\ApiException
     */
    public function entity() {

        if (empty($this->entity)) {

            throw $this->api->exception(Api::EXCEPTION_NO_ENTITY);
        }

        return $this->entity->load($this->with);
    }

    /**
     * @param $query
     * @param $count_column
     * @param $page
     * @param $per_page
     * @param $order_by
     * @param $order_dir
     * @return ApiCollection
     */
    public function paginate($query, $count_column, $page, $per_page, $order_by, $order_dir) {

        $start = ($page - 1) * $per_page;

        $query->orderBy($order_by, $order_dir);

        $total_num_results = $query->count($count_column);
        $collection = $query->skip($start)->take($per_page)->get();

        return new ApiCollection($collection, $page, $per_page, $total_num_results);
    }

    public function defaultValidationRules() {

        return array(
            'with' => array('sometimes', 'array', 'with'),
            'include_featured' => array('sometimes', 'boolean'),
            'order_by' => array('required', 'not_in:password,remember_token'),
            'order_dir' => array('required', 'in:'.implode(',', array('asc', 'desc', 'ASC', 'DESC'))),
            'page' => array('required', 'integer', 'min:1'),
            'per_page' => array('required', 'integer', 'max:1000', 'min:0'),
            'show_deleted' => array('boolean', Api::FILTER_ADMINS_ONLY.':'.implode(',', array(true, 1, 'true', '1')))
        );
    }

    public function validateRequestedEntityRelations($with) {

        if (is_array($with)) {

            if (count(array_diff($with, $this->public_entity_relations))) {

                throw $this->api->exception(Api::EXCEPTION_VALIDATION, 'Invalid relationships found in "with".');
            }

            if (count(array_intersect($with, $this->private_entity_relations))) {

                throw $this->api->exception(Api::EXCEPTION_VALIDATION, 'Invalid relationships found in "with".');
            }
            $this->with = $with;
        }

    }
}