<?php
namespace Plugins\FluxAPI\Core\FieldValidator;

class Required extends \FluxAPI\FieldValidator
{
    public function validate($value, \FluxAPI\Field $field, \FluxAPI\Model $model, array $options = array())
    {
        if (empty($value)) {
            $model->addError(new \FluxAPI\Exception\ValidateException(sprintf('The field "%s" is required.', $field->name)));
            return false;
        }
        return true;
    }
}