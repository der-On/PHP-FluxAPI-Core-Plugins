<?php
namespace Plugins\FluxAPI\Core\Controller;

class Cache extends \FluxAPI\Controller
{
    public static function getActions()
    {
        return array(
            'clearAll',
            'clear'
        );
    }

    public function clear($type)
    {
        $this->_api['caches']->clear($type);
        return true;
    }

    public function clearAll()
    {
        $this->_api['caches']->clearAll();
        return true;
    }
}