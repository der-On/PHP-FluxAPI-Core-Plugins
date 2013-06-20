<?php
namespace Plugins\FluxAPI\Core\FieldValidator;

use \FluxAPI\FieldValidator;
use \FluxAPI\Query;

class Alias extends FieldValidator
{
    public function validate($value, \FluxAPI\Field $field, \FluxAPI\Model $model, array $options = array())
    {
        if (empty($value) && isset($options['source'])) {
            $source = $options['source'];
            $value = $model->$source;
        }

        // convert umlauts
        $alias = preg_replace(array('/Ä/', '/Ö/', '/Ü/', '/ä/', '/ö/', '/ü/'), array('Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue'), $value);

        // convert to ascii only
        $alias = iconv('UTF-8', 'ASCII//TRANSLIT', $alias);

        // remove non alphanumeric characters
        $alias = preg_replace("/[^a-zA-Z0-9\/_\.|+ -]/", '', $alias);

        // trim and convert to lowercase
        $alias = strtolower(trim($alias, '-'));

        // convert spaces and such
        $alias = preg_replace("/[\/_\.|+ -]+/", '-', $alias);

        $alias = $this->_getNonCollidingAlias($model, $alias);

        $name = $field->name;
        $model->$name = $alias;

        return true;
    }

    protected function _getNonCollidingAlias($model, $alias, $i = 0)
    {
        $model_name = $model->getModelName();

        if ($i > 0) {
            $_alias = $alias . '-' . $i;
        } else {
            $_alias = $alias;
        }

        $query = new Query();
        $query
            ->filter('select', array('alias'))
            ->filter('equal', array('alias', $_alias))
            ->setType(Query::TYPE_COUNT)
            ->setModelName($model_name)
        ;

        if (!$model->isNew()) {
            $query->filter('not', array('id', $model->id));
        }

        $count = $this->_api['storages']->getStorage($model_name)->executeQuery($query);

        if ($count == 0) {
            return $_alias;
        } else {
            return $this->_getNonCollidingAlias($model, $alias, $i + 1);
        }
    }
}