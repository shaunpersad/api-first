<?php


namespace Api\V2;



class UsersResource extends AbstractEntityResource {

    /**
     * @var array
     */
    protected $with = array('address.state', 'address.country');


    /**
     *
     *
     * @param AbstractResource $users
     * @return array
     */
    public static function endpointFilters(AbstractResource $users = null) {

        return array(
            'all' => Api::FILTER_ADMINS_ONLY, // /users
            'create' => Api::FILTER_PUBLIC, // /users/create
            'resetPassword' => Api::FILTER_PUBLIC, // /users/reset-password
            'sendResetPasswordEmail' => Api::FILTER_PUBLIC, // /users/send-reset-password-email
            'get' => Api::FILTER_OWNER_OR_ADMINS, // /users/{user_id}
        );
    }

    /**
     * @return mixed
     */
    public function query() {

        return User::query();
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

        $collection = $this->paginate($query, 'users.id', $page, $per_page, $order_by, $order_dir);

        return $collection;

    }

    protected function create($params = array()) {

        $defaults = array(
            'email' => $email = null,
            'password' => $password = null,
            'repeat_password' => $repeat_password = null
        );

        $rules = array(
            'email' => array(
                'required',
                'email'
            ),
            'password' => array(
                'required',
                'min:6'
            ),
            'repeat_password' => array(
                'required',
                'same:password'
            )
        );

        $params = $this->validateParams($defaults, $params, $rules);

        extract($params);

        $user = User::registeredWithEmail($email)->first();

        if (!empty($user)) {

            throw $this->api->exception(Api::EXCEPTION_USER_EXISTS);
        }

        $user = User::makeNormalUser($email, $password);

        if ($user->id) {

            $this->entity = $user;

            $user = $this->entity();

            Mail::send(
                'emails.after-registration',
                array(),
                function(Message $message) use ($user) {
                    $message->to($user->email)->subject('Welcome!');
                }
            );
            return $user;
        }
        throw $this->api->exception();
    }


    /**
     * @param array $params
     * @return User
     * @throws \Api\Exceptions\ApiException
     */
    protected function resetPassword($params = array()) {

        $defaults = array(
            'verification_code' => $verification_code = null,
            'email' => $email = null,
            'password' => $password = null,
            'repeat_password' => $repeat_password = null
        );

        $rules = array(
            'verification_code' => array(
                'required'
            ),
            'email' => array(
                'required',
                'email',
                'exists:users,email,login_type,'.User::LOGIN_TYPE_NORMAL.',deleted_at,NULL'
            ),
            'password' => array(
                'required',
                'min:6'
            ),
            'repeat_password' => array(
                'required',
                'same:password'
            )
        );

        $params = $this->validateParams($defaults, $params, $rules);

        extract($params);

        /**
         * @var User
         */
        $user = User::registeredWithEmail($email)->first();

        $hashed_verification_code = $user->verification_code;

        if (!Hash::check($verification_code, $hashed_verification_code)) {

            throw $this->api->exception(Api::EXCEPTION_INSUFFICIENT_PERMISSIONS, 'This verification code is not valid.');
        }

        $user->generateVerificationCode();
        $user->password = Hash::make($password);

        if ($user->save()) {

            OauthRefreshToken::where('user_id', '=', $user->id)->delete();
            OauthAccessToken::where('user_id', '=', $user->id)->delete();

            $this->entity = $user;

            return $this->entity();
        }

        throw $this->api->exception();
    }

    /**
     * @param array $params
     * @return string
     * @throws \Api\Exceptions\ApiException
     */
    protected function sendResetPasswordEmail($params = array()) {

        $defaults = array(
            'email' => $email = null
        );

        $rules = array(
            'email' => array(
                'required',
                'email',
                'exists:users,email,login_type,'.User::LOGIN_TYPE_NORMAL.',deleted_at,NULL'
            )
        );
        $params = $this->validateParams($defaults, $params, $rules);

        extract($params);

        /**
         * @var User
         */
        $user = User::registeredWithEmail($email)->first();

        if ($verification_code = $user->generateVerificationCode()) {

            $url = $_ENV['SITE_URL'].'/reset-password?verification_code='.$verification_code.'&email='.$email;

            Mail::send(
                'emails.reset-password',
                array(
                    'reset_password_url' => $url,
                    'email' => $email
                ),
                function(Message $message) use ($email) {
                    $message->to($email)->subject('Oops! Lost your password?');
                }
            );

            return $email;
        }
        throw $this->api->exception();
    }



    protected function restore($params = array()) {

        $defaults = array(
            'user_id' => $user_id = null
        );

        $params = $this->validateParams($defaults, $params, array(
            'user_id' => array(
                'required',
                'exists:users,id'
            )
        ));

        extract($params);

        $user = User::withTrashed()->where('id', '=', $user_id)->first();

        $user->restore();

        $this->entity = $user;

        return $this->entity();

    }

    public function cats(Cat $cat = null) {

        $user = $this->entity();

        if (!empty($cat) && !$user->cats()->where('id', $cat->id)->exists()) {

            throw $this->api->exception(Api::EXCEPTION_NO_PARENT_ENTITY, 'This Cat does not belong to this User.');
        }

        return new UsersCatsResource($this->api, $cat, $this);
    }

}