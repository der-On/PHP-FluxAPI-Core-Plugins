<?php

namespace Plugins\FluxAPI\Controller;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use FluxAPI\Query;
use Symfony\Component\Serializer\Exception\RuntimeException;

class User extends \FluxAPI\Controller
{
    protected $_psl;

    public $config = array(
        'session' => array(
            'session.storage.options' => array(
                'cookie_lifetime' => 604800, // 7 days
            )
        ),
        'auth' => array(
            'require_email' => FALSE, // if set to TRUE, on logins the user email will be required instead of the username
        )
    );

    public function __construct(\FluxAPI\Api $api)
    {
        parent::__construct($api);

        $this->config = array_replace_recursive($this->config, $this->_api->config['plugin.options']['FluxAPI']);

        // add sessions service provider if not already present
        if (!isset($this->_api->app['session'])) {
            $this->_api->app->register(new \Silex\Provider\SessionServiceProvider(), $this->config['session']);

            // start a session
            $this->_api->app['session']->start();
        }

        $this->_psl = new \phpSec\Core();
        $this->_psl['crypt/hash']->method = \phpSec\Crypt\Hash::PBKDF2;

        $this->_registerModelEvents();
    }

    protected function _registerModelEvents()
    {
        print('registering model events');
        // register listeners for users to hash passwords
        $this->_api->on(ModelEvent::CREATE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\FluxAPI\\Model\\User') && $model->isNew()) {
                print("user created");
                $model->password = $this->getEncryptedPassword($model->password);
            }
        }, \FluxAPI\Api::EARLY_EVENT);

        $this->_api->on(ModelEvent::BEFORE_SAVE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\FluxAPI\\Model\\User')) {
                // TODO: only encrypt non-encrypted passwords, but how to find out if password was changed during this update?
            }
        }, \FluxAPI\Api::EARLY_EVENT);
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
        if ($this->isLoggedIn()) {
            throw new \RuntimeException('There is already a user logged in. Please log out first.');
            return FALSE;
        }

        $query = new Query();
        $query
            ->filter('=', array(($this->config['auth']['require_email']) ? 'email' : 'username', $username))
            ->filter('=', array('password', $this->getEncryptedPassword($password) ));

        $user = $this->_api->loadUser($query);

        if ($user) {
            $this->_api->app['session']->set('userId', $user->id);
            return TRUE;
        } else {
            throw new \InvalidArgumentException('No user with the given username or incorrect password.');
            return FALSE;
        }
    }

    public function logout()
    {
        $this->_api->app['session']->remove('userId');
    }

    public function isLoggedIn()
    {
        $userId = $this->_api->app['session']->get('userId', null);
        if (!empty($userId)) {
            $query = new Query();
            $query->filter('=',array('id', $userId));

            return ($this->_api->countUsers($query) > 0);
        }
        return FALSE;
    }

    public function getCurrent()
    {
        $userId = $this->_api->app['session']->get('userId', null);

        if(!empty($userId)) {
            return $this->_api->loadUser($userId);
        }

        return NULL;
    }

    public function getEncryptedPassword($password)
    {
        // TODO: do we really need a full stack security lib for this or can't we simply use hash()?
        $hash = $this->_psl['crypt/hash']->create($password);

        return $hash;
    }
}