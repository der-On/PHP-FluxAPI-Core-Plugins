<?php
namespace Plugins\FluxAPI\Core\FieldValidator;

class StripTags extends \FluxAPI\FieldValidator
{
    public function validate($value, \FluxAPI\Field $field, \FluxAPI\Model $model, array $options = array())
    {
        $name = $field->name;
        $model->$name = strip_tags($value);
        return TRUE;
    }
}