<?php

namespace Plugins\FluxAPI\Core\Model;

use \FluxAPI\Field;

class UserGroup extends \Plugins\FluxAPI\Core\Model
{
    public function defineFields()
    {
        parent::defineFields();

        $this->addField(new Field(array(
            'type' => Field::TYPE_ARRAY,
            'name' => 'permissions'
        )))
        ->addField(new Field(array(
            'name' => 'users',
            'type' => Field::TYPE_RELATION,
            'relationType' => Field::HAS_MANY,
            'relationModel' => 'User',
        )));
    }
}