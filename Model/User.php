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
            'type' => Field::TYPE_DATETIME,
            'name' => 'lastLoginAt'
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_DATETIME,
            'name' => 'lastLogoutAt'
        )))
        ->addField(new Field(array(
            'name' => 'userGroups',
            'type' => Field::TYPE_RELATION,
            'relationType' => Field::BELONGS_TO_MANY,
            'relationModel' => 'UserGroup',
            'relationField' => 'users',
        )));
    }

    public function hasPermission($name)
    {
        $has = false;

        if (is_array($this->permissions)) {
            $has = in_array($name, $this->permissions);
        }


        if (!$has && !empty($this->userGroups)) {
            foreach($this->userGroups as $userGroup) {
                if (is_array($userGroup->permissions)) {
                    $has = in_array($name, $userGroup->permissions);

                    if ($has) {
                        return $has;
                    }
                }
            }
        }

        return $has;
    }
}