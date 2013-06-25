<?php
namespace Plugins\FluxAPI\Core\Controller;

class Cache extends \FluxAPI\Controller
{
    public static function getActions()
    {
        return array(
            'clearAll'
        );
    }

    public function clearAll()
    {
        $this->_api['caches']->clearAll();
        return TRUE;
    }
}