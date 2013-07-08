<?php
namespace Plugins\FluxAPI\Core\FieldValidator;

class Object extends \FluxAPI\FieldValidator
{
    public function validate($value, \FluxAPI\Field $field, \FluxAPI\Model $model, array $options = array())
    {
        $name = $field->name;

        if (!empty($value)) {
            // model already has an object of that kind so we will add the additional values from the passed array
            if (!empty($model->$name) && is_object($model->$name) && is_array($value)) {
                $this->_arrayToObject($value, $model->$name);
                return true;
            }
            // model does not have an object so value must be converted from an array to the object and attached to the model
            elseif (isset($options['class']) && is_array($value) && count(array_keys($value)) > 0) {
                $class = $options['class'];
                $object = new $class();
                $this->_arrayToObject($value, $object);

                $model->$name = $object;
                return true;
            }

            if (!is_object($value)) {
                $model->addError(new \FluxAPI\Exception\ValidateException(sprintf('The field "%s" is no object.', $field->name)));

                return false;
            }
            elseif (isset($options['class']) && !($value instanceof $options['class'])) {
                $model->addError(new \FluxAPI\Exception\ValidateException(sprintf('The field "%s" is no instance of "%s".', $field->name, $options['class'])));

                return false;
            }
        }

        return true;
    }

    protected function _arrayToObject($arr, $object)
    {
        $class = get_class($object);

        foreach($arr as $key => $value) {
            if (property_exists($class, $key)) {
                if (!is_object($object->$key)) {
                    $object->$key = $value;
                }
                elseif (is_array($value)) {
                    $this->_arrayToObject($value, $object->$key);
                }
            }
        }
    }
}