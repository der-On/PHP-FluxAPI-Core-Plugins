<?php
namespace Plugins\FluxAPI\Core\Model;

use \FluxAPI\Field;

class File extends \Plugins\FluxAPI\Core\Model
{
    public function defineFields()
    {
        parent::defineFields();

        $this
            ->addField(new Field(array(
                'name' => 'title',
                'type' => Field::TYPE_STRING,
                'validators' => array('StripTags'),
            )))
            // path/uri to the file
            ->addField(new Field(array(
                'name' => 'path',
                'type' => Field::TYPE_STRING,
                'length' => 2048,
                'validators' => array('Required'),
            )))
            // path to the preview image
            ->addField(new Field(array(
                'name' =>  'preview',
                'type' => Field::TYPE_STRING,
                'length' => 2048,
            )))
            // filetype/extension
            ->addField(new Field(array(
                'name' => 'mimetype',
                'type' => Field::TYPE_STRING,
                'length' => 64,
                'default' => 'text',
                'validators' => array('Required'),
            )))
            // file size in bytes
            ->addField(new Field(array(
                'name' => 'size',
                'type' => Field::TYPE_INTEGER,
            )))
            ->addField(new Field(array(
                'name' => 'weight',
                'type' => Field::TYPE_INTEGER,
                'default' => 0,
            )))
            ;
    }
}