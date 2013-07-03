<?php
namespace Plugins\FluxAPI\Core;

use \FluxAPI\Event\ModelEvent;

class ModelEvents
{
    public static function register(\FluxAPI\Api $api)
    {
        // register listeners for models to update author, updatedAt and createdAt
        $api->on(ModelEvent::BEFORE_SAVE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\FluxAPI\\Core\\Model')) {
                $now = new \DateTime();

                if  ($model->isNew()) {
                    $model->createdAt = $now;
                }
                $model->updatedAt = $now;
            }
        }, \FluxAPI\Api::EARLY_EVENT);

        $api->on(ModelEvent::BEFORE_UPDATE, function(ModelEvent $event) use ($api) {
            $model_name = $event->getModelName();

            $model_class = $api['plugins']->getPluginClass('Model', $model_name);

            if ($model_class) {
                $model = new $model_class($api);

                if (is_subclass_of($model, '\\Plugins\\FluxAPI\\Core\\Model')) {
                    $event->getQuery()->setDataField('updatedAt', new \DateTime());
                }
            }
        }, \FluxAPI\Api::EARLY_EVENT);
    }
}