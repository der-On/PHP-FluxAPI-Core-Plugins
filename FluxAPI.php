<?php
namespace Plugins\FluxAPI;

class FluxAPI extends \FluxAPI\Plugin
{
    public static function register(\FluxAPI\Api $api)
    {
        self::_registerValidators($api);
        self::_registerRest($api);
        self::_registerModelEvents($api);
    }

    protected static function _registerValidators(\FluxAPI\Api $api)
    {
        // register validator service if not present yet
        if (!isset($api->app['validator'])) {
            $api->app->register(new \Silex\Provider\ValidatorServiceProvider());
        }
    }

    protected static function _registerRest(\FluxAPI\Api $api)
    {
        // do not enable REST when it's disabled in plugin.options
        if (!in_array('FluxAPI/Rest',$api->config['plugin.options']['disabled'])) {
            // create RESTfull webservice
            $rest = new Rest($api);
        }
    }

    protected static function _registerModelEvents(\FluxAPI\Api $api)
    {
        if (!in_array('FluxAPI/ModelEvents', $api->config['plugin.options']['disabled'])) {
            ModelEvents::register($api);
        }
    }
}
