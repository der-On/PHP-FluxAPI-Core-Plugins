<?php
namespace Plugins\FluxAPI\Core\FieldValidator;

class Object extends \FluxAPI\FieldValidator
{
    public function validate($value, \FluxAPI\Field $field, \FluxAPI\Model $model, array $options = array())
    {
        if (!empty($value)) {
            // value must be converted from an array to the object
            if (isset($options['class']) && is_array($value) && count(array_keys($value)) > 0) {
                $class = $options['class'];
                $object = new $class();
                $this->_arrayToObject($value, $object);
                $name = $field->name;
                $model->$name = $object;
                return true;
            }

            if (!is_object($value)) {
                $model->addError(new \FluxAPI\Exception\ValidateException(sprintf('The field "%s" is no object.', $field->name)));

                return false;
            }
            elseif (isset($options['class']) && !is_subclass_of($value, $options['class'])) {
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