<?php
namespace Plugins\FluxAPI\FieldValidator;

class Email extends \FluxAPI\FieldValidator
{
    public function validate($value, \FluxAPI\Field $field, \FluxAPI\Model $model)
    {
        return (!empty($value));
    }
}