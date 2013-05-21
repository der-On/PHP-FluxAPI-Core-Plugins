<?php
namespace Plugins\FluxAPI;

use \FluxAPI\Event\ModelEvent;

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
        // register listeners for models to update author, updatedAt and createdAt
        $api->on(ModelEvent::CREATE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\FluxAPI\\Model') && $model->isNew()) {
                $now = new \DateTime();
                $model->createdAt = $now;
            }
        }, \FluxAPI\Api::EARLY_EVENT);

        $api->on(ModelEvent::BEFORE_SAVE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\FluxAPI\\Model')) {
                $now = new \DateTime();
                $model->updatedAt = $now;
            }
        }, \FluxAPI\Api::EARLY_EVENT);
    }
}
