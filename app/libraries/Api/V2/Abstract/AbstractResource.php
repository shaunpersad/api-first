<?php


namespace Api\V2;


use Log;
use Validator;

abstract class AbstractResource {

    /**
     * Parent Api object.
     *
     * @var Api
     */
    protected $api;

    /**
     * The parent resource.
     *
     * e.g. for /users/{user_id}/recipients,
     * the current resource would be the UsersRecipientsResource,
     * while the parent resource would be the UsersResource
     *
     * @var AbstractEntityResource|null
     */
    protected $parent_resource;

    public function __construct(Api $api, AbstractResource $parent_resource = null) {

        $this->api = $api;
        $this->parent_resource = $parent_resource;
    }


    /**
     * @param AbstractResource $resource
     * @return array
     */
    public static function endpointFilters(AbstractResource $resource = null) {
        return array();
    }


    public final function __call($endpoint, $arguments = array()) {

        if (method_exists($this, $endpoint)) {

            $class = get_called_class();

            $endpoint_filters = $class::endpointFilters($this);

            if ($endpoint_filters && array_key_exists($endpoint, $endpoint_filters)) {

                if (!is_array($endpoint_filters[$endpoint])) {

                    $endpoint_filters[$endpoint] = array($endpoint_filters[$endpoint]);
                }
                foreach ($endpoint_filters[$endpoint] as $endpoint_filter) {

                    if (is_callable($endpoint_filter)) {
                        call_user_func($endpoint_filter);
                    } else {
                        $this->api->filter($endpoint_filter);
                    }
                }
                return call_user_func_array(array($this, $endpoint), $arguments);
            }
            Log::error('Please implement "public static function endpointFilters(AbstractResource $resource = null)" in your '.$class.' class.');
            throw $this->api->exception(Api::EXCEPTION_UNRESOLVABLE_PERMISSIONS, 'Permissions could not be determined for this endpoint.');
        }
        throw $this->api->exception(Api::EXCEPTION_ENDPOINT_NOT_FOUND);
    }

    public function validateParams($defaults = array(), $params = array(), $rules = array()) {

        $params = array_merge($defaults, $params);

        $default_rules = $this->defaultValidationRules();

        foreach ($params as $key => $value) {

            if (array_key_exists($key, $default_rules) && !array_key_exists($key, $rules)) {
                $rules[$key] = $default_rules[$key];
            }
        }

        $validator = Validator::make($params, $rules);

        if ($validator->fails()) {

            $message = $validator->messages()->first();
            throw $this->api->exception(Api::EXCEPTION_VALIDATION, $message);
        }
        return $params;
    }

    public function defaultValidationRules() {

        return array();
    }

    public function createCacheKey($params = array()) {

        $obj = array(
            'class' => get_called_class(),
            'params' => $params
        );
        return json_encode($obj);
    }
} 