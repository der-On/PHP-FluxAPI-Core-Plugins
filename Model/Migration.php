<?php
namespace Plugins\FluxAPI\Core\Model;

use \FluxAPI\Field;

class Migration extends \FluxAPI\Model
{
    public function defineFields()
    {
        parent::defineFields();

        $this->addField(new Field(array(
            'name' => 'migration',
            'type' => Field::TYPE_STRING
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_DATETIME,
            'name' => 'createdAt'
        )));
    }
}