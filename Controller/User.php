<?php

namespace Plugins\FluxAPI\Controller;

use FluxAPI\Query;

class User extends \FluxAPI\Controller
{
    public $config = array(
        'session' => array(
            'session.storage.options' => array(
                'cookie_lifetime' => 604800, // 7 days
            )
        )
    );

    public function __construct(\FluxAPI\Api $api)
    {
        parent::__construct($api);

        $this->config = array_replace_recursive($this->config, $this->_api->config['plugin.options']['FluxAPI']);

        // add sessions service provider if not already present
        if (!isset($this->_api->app['session'])) {
            $this->_api->app->register(new \Silex\Provider\SessionServiceProvider(), $this->config['session']);
        }
    }

    /**
     * Returns a list of method names that are considered to be actions known to the API.
     * Override this in your Controller.
     *
     * @return array
     */
    public static function getActions()
    {
        return array(
          'login',
          'logout'
        );
    }

    public function login($username, $password)
    {
        $query = new Query();
        $query
            ->filter('=', array('username', $username))
            ->filter('=', array('password'), $password);

        // TODO: create password hash

        $user = $this->_api->loadUser($query);

        if ($user) {

        }
    }

    public function logout()
    {
        $this->_api->app['session']->set('userId', null);
    }

    public function isLoggedIn($token)
    {
        return FALSE;
    }

    public function getCurrentUser()
    {
        return NULL;
    }
}