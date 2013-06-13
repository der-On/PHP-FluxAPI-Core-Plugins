<?php

namespace Plugins\FluxAPI\Core\Model;

use \FluxAPI\Field;

class User extends \Plugins\FluxAPI\Core\Model
{
    public function defineFields()
    {
        parent::defineFields();

        $this->addField(new Field(array(
            'type' => Field::TYPE_STRING,
            'name' => 'username',
            'validators' => array('Required')
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_STRING,
            'name' => 'email',
            'validators' => array('Required','Email')
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_STRING,
            'name' => 'password',
            'validators' => array('Required')
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_STRING,
            'name' => 'token'
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_ARRAY,
            'name' => 'permissions'
        )))
        ->addField(new Field(array(
            'name' => 'usergroups',
            'type' => Field::TYPE_RELATION,
            'relationType' => Field::BELONGS_TO_MANY,
            'relationModel' => 'UserGroup'
        )));
    }
}