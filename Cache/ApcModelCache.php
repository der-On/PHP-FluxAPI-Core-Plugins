<?php
namespace Plugins\FluxAPI\Core\Cache;

use \FluxAPI\Cache;
use \FluxAPI\Cache\CacheOptions;
use \FluxAPI\Cache\CacheSource;

class ApcModelCache extends MemoryModelCache
{
    protected $_driver_class = '\Doctrine\Common\Cache\ApcCache';
}