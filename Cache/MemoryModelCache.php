<?php
namespace Plugins\FluxAPI\Core\Cache;

use FluxAPI\Cache\CacheOptions;
use FluxAPI\Cache\CacheSource;
use FluxAPI\Cache;

class MemoryModelCache extends \FluxAPI\Cache
{
    protected $_driver = NULL;
    protected $_driver_class = '\Doctrine\Common\Cache\ArrayCache';

    public function __construct(\FluxAPI\Api $api)
    {
        parent::__construct($api);
        $this->_driver = new $this->_driver_class();
    }

    public function getCached($type, CacheSource $source, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            $hash = $source->toHash();

            if ($this->_driver->contains($hash)) {
                $resource = $this->_driver->fetch($hash);

                $createMethod = 'create' . $source->model_name;

                foreach($resource as $i => $data) {
                    $id = $resource[$i]['id'];
                    $resource[$i] = $this->_api->$createMethod($data);
                    $resource[$i]->id = $id;
                    $resource[$i]->notNew();
                }

                return $resource;
            }
        }

        return NULL;
    }

    public function store($type, CacheSource $source, $resource, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            $hash = $source->toHash();

            $this->_driver->delete($hash);

            // convert to array
            if (!is_array($resource)) {
                $resource = array($resource->toArray());
            }
            else {
                foreach($resource as $i => $instance) {
                    $resource[$i] = $resource[$i]->toArray();
                }
            }

            $this->_driver->save($hash, $resource);

            foreach($resource as $instance) {
                $hash = $source->model_name . '/' . $instance['id'];
                $this->_driver->delete($hash);
                $this->_driver->save($hash, $instance);
            }
        }
    }

    public function remove($type, CacheSource $source, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            $hash = $source->toHash();

            $this->_driver->delete($hash);

            if (!empty($source->instances)) {
                foreach($source->instances as $instance) {
                    $hash = $source->model_name . '/' . $instance->id;
                    $this->_driver->delete($hash);
                }
            }
        }
    }

    public function clear($type)
    {
        if ($type == Cache::TYPE_MODEL) {
            $this->_driver->deleteAll();
        }
    }
}