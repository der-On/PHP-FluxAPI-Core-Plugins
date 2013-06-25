<?php
namespace Plugins\FluxAPI\Core\Cache;

use FluxAPI\Cache\CacheOptions;
use FluxAPI\Cache\CacheSource;
use FluxAPI\Cache;

class MemoryModelCache extends \FluxAPI\Cache
{
    protected $_models = array();

    public function getCached($type, CacheSource $source, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            $hash = $source->toHash();

            if (isset($this->_models[$hash])) {
                return $this->_models[$hash];
            }
        }

        return NULL;
    }

    public function store($type, CacheSource $source, $resource, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            $hash = $source->toHash();

            $this->_models[$hash] = $resource;

            foreach($resource as $instance) {
                $this->_models[$instance->id] = $instance;
            }
        }
    }

    public function remove($type, CacheSource $source, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            $hash = $source->toHash();

            unset($this->_models[$hash]);

            if (!empty($source->instances)) {
                foreach($source->instances as $instance) {
                    if (isset($this->_models[$instance->id])) {
                        unset($this->_models[$instance->id]);
                    }
                }
            }
        }
    }

    public function clear($type)
    {
        if ($type == Cache::TYPE_MODEL) {
            $this->_models = array();
        }
    }
}