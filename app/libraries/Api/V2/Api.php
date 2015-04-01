<?php


namespace Api\V2;


use Api\Exceptions\ApiException;
use Brand;
use EducationalMovement;
use Input;
use Movement;
use Postcard\Filesystem\Filesystem;
use Role;
use Shaunpersad\ApiFoundation\Models\OauthClient;
use SponsorInterface;
use User;
use Validator;

class Api {

    const FILTER_OWNER_OR_ADMINS = 'owner_or_admins';
    const FILTER_ADMINS_ONLY = 'admins_only';
    const FILTER_NOT_OWNER = 'not_owner';
    const FILTER_MUST_HAVE_USER = 'must_have_user';
    const FILTER_MUST_HAVE_RESOURCE_OWNER = 'must_have_resource_owner';
    const FILTER_PUBLIC = 'public';
    const FILTER_INTERNAL_SERVICE_ONLY = 'internal_service_only';

    const EXCEPTION_UNKNOWN = 'unknown';
    const EXCEPTION_NO_USER = 'no_user';
    const EXCEPTION_USER_EXISTS = 'user_exists';
    const EXCEPTION_NO_ENTITY = 'no_entity';
    const EXCEPTION_NO_PARENT_ENTITY = 'no_parent_entity';
    const EXCEPTION_INSUFFICIENT_PERMISSIONS = 'insufficient_permissions';
    const EXCEPTION_UNRESOLVABLE_PERMISSIONS = 'unresolvable_permissions';
    const EXCEPTION_MISSING_PARAM = 'missing_param';
    const EXCEPTION_VALIDATION = 'validation_error';
    const EXCEPTION_ENDPOINT_FOR_METHOD_NOT_FOUND = 'endpoint_for_method_not_found';
    const EXCEPTION_ENDPOINT_NOT_FOUND = 'endpoint_not_found';
    const EXCEPTION_GRANT_TYPE_NOT_SUPPORTED = 'grant_type_not_supported';
    const EXCEPTION_APPLICATION_NOT_APPROVED = 'application_not_approved';

    /**
     * @var null|User
     */
    protected $user;

    /**
     * @var null|User
     */
    protected $resource_owner;


    public function __construct($client_id = null) {

        if ($user = Auth::user()) {

            $this->user = $user;
        }

        if (empty($this->user)) {

            if (empty($client_id)) {
                $client_id = Input::get('client_id');
            }

            if (!empty($client_id)) {

                $client = OauthClient::find($client_id);

                if (empty($client)) {

                    throw $this->exception(Api::EXCEPTION_APPLICATION_NOT_APPROVED);
                }
            } else {
                throw $this->exception(Api::EXCEPTION_APPLICATION_NOT_APPROVED);
            }
        }

    }

    public function users(User $user = null) {

        return new UsersResource($this, $user);
    }

    public function cats(Cat $cat = null) {

        return new CatsResource($this, $user);
    }

    /**
     * @param string $key
     * @return null|User
     * @throws \Api\Exceptions\ApiException
     */
    public function filter($key) {

        switch ($key) {
            case self::FILTER_ADMINS_ONLY:
                if (empty($this->user)) {

                    throw $this->exception(Api::EXCEPTION_NO_USER);
                }
                if ($this->user->hasRole(Role::ROLE_SUPER_ADMIN) || $this->user->hasRole(Role::ROLE_ADMIN)) {

                    return $this->user;
                }
                throw $this->exception(Api::EXCEPTION_INSUFFICIENT_PERMISSIONS);
                break;
            case self::FILTER_MUST_HAVE_USER:
                if (!empty($this->user)) {

                    return $this->user;
                }
                throw $this->exception(Api::EXCEPTION_NO_USER);
                break;
            case self::FILTER_MUST_HAVE_RESOURCE_OWNER:
                if (!empty($this->resource_owner)) {

                    return $this->resource_owner;
                }
                throw $this->exception(Api::EXCEPTION_MISSING_PARAM, 'No resource owner found.');
                break;
            case self::FILTER_OWNER_OR_ADMINS:
                $user = $this->filter(self::FILTER_MUST_HAVE_USER);
                $resource_owner = $this->filter(self::FILTER_MUST_HAVE_RESOURCE_OWNER);

                if ($resource_owner->id == $user->id) {

                    return $resource_owner;
                }
                $this->filter(self::FILTER_ADMINS_ONLY);
                break;
            case self::FILTER_NOT_OWNER:
                $user = $this->filter(self::FILTER_MUST_HAVE_USER);
                $resource_owner = $this->filter(self::FILTER_MUST_HAVE_RESOURCE_OWNER);

                if ($resource_owner->id != $user->id) {

                    return $resource_owner;
                }
                throw $this->exception(self::EXCEPTION_INSUFFICIENT_PERMISSIONS, 'The logged in user must not be the resource owner.');
                break;
            case self::FILTER_PUBLIC:
                break;
            default:
                throw $this->exception(Api::EXCEPTION_UNRESOLVABLE_PERMISSIONS);
        }

        return null;
    }
    /**
     * @param string $key
     * @param string|null $new_description
     * @return ApiException
     */
    public function exception($key = self::EXCEPTION_UNKNOWN, $new_description = null) {

        switch ($key) {
            case self::EXCEPTION_NO_USER:
                $code = 401;
                $description = 'A logged in user is required';
                break;
            case self::EXCEPTION_USER_EXISTS:
                $code = 401;
                $description = 'A user with this email address already exists.';
                break;
            case self::EXCEPTION_NO_ENTITY:
                $code = 404;
                $description = 'This entity could not be found.';
                break;
            case self::EXCEPTION_NO_PARENT_ENTITY:
                $code = 404;
                $description = 'The parent entity could not be found.';
                break;
            case self::EXCEPTION_INSUFFICIENT_PERMISSIONS:
                $code = 401;
                $description = 'This user does not have permission.';
                break;
            case self::EXCEPTION_UNRESOLVABLE_PERMISSIONS:
                $code = 403;
                $description = 'This user\'s permissions could not be determined.';
                break;
            case self::EXCEPTION_MISSING_PARAM:
                $code = 400;
                $description = 'A required request parameter is missing.';
                break;
            case self::EXCEPTION_VALIDATION:
                $code = 400;
                $description = 'Please check that all submitted values are valid.';
                break;
            case self::EXCEPTION_ENDPOINT_FOR_METHOD_NOT_FOUND:
                $code = 404;
                $description = 'The specified endpoint was not found using this HTTP method.';
                break;
            case self::EXCEPTION_ENDPOINT_NOT_FOUND:
                $code = 404;
                $description = 'The specified endpoint was not found.';
                break;
            case self::EXCEPTION_GRANT_TYPE_NOT_SUPPORTED:
                $code = 501;
                $description = 'This grant type is not supported.';
                break;
            case self::EXCEPTION_APPLICATION_NOT_APPROVED:
                $code = 401;
                $description = 'This application is not approved to access the API.';
                break;
            default:
                $key = self::EXCEPTION_UNKNOWN;
                $code = 500;
                $description = 'An unknown error occurred.';
        }

        if (!empty($new_description)) {

            $description = $new_description;
        }

        $exception = ApiException::make($key, $code, $description);

        return $exception;
    }

} 