<?php
namespace Plugins\FluxAPI\Core\Cache;

use FluxAPI\Cache\CacheOptions;
use FluxAPI\Cache\CacheSource;
use FluxAPI\Cache;

class MemoryModelCache extends \FluxAPI\Cache
{
    protected $_driver = NULL;
    protected $_driver_class = '\Doctrine\Common\Cache\ArrayCache';

    protected $_options = array(
        'lifetime' => 0
    );

    protected function _setOptions()
    {
        $plugin_path = str_replace('\\','/', get_class($this));
        $plugin_path = str_replace('Plugins','', $plugin_path);
        $plugin_path = ltrim($plugin_path, '/');

        if (isset($this->_api->config['plugin.options'][$plugin_path])) {
            $this->_options = array_merge($this->_options, $this->_api->config['plugin.options'][$plugin_path]);
        }
    }

    public function __construct(\FluxAPI\Api $api)
    {
        parent::__construct($api);
        $this->_driver = new $this->_driver_class();

        $this->_setOptions();
    }

    public function getCached($type, CacheSource $source, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            if (!empty($source->query)) {
                $hash = $source->toHash(); // query based hash

                if ($this->_driver->contains($hash)) {
                    $resource = $this->_driver->fetch($hash);

                    $models = new \FluxAPI\Collection\ModelCollection();

                    foreach($resource as $data) {
                        $data = unserialize($data);
                        $model = $this->_api->create($source->model_name, $data);

                        // api->create will not set the id from the data, so we have to do it
                        $model->id = $data['id'];
                        $model->notNew();
                        $models->push($model);
                    }

                    return $models;
                }
            }
        }

        return NULL;
    }

    public function store($type, CacheSource $source, $resource, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            if (!empty($source->query)) {
                // convert to array
                if (\FluxAPI\Collection\ModelCollection::isInstance($resource)) {
                    $resource = $resource->toArray();
                }
                else {
                    $resource = array($resource);
                }

                foreach($resource as $i => $model) {
                    $resource[$i] = serialize($model->toArray(false));
                }

                $hash = $source->toHash();
                $this->_driver->delete($hash);
                $this->_driver->save($hash, $resource);
            }
        }
    }

    public function remove($type, CacheSource $source, CacheOptions $options = null)
    {
        if ($type == Cache::TYPE_MODEL) {
            if (!empty($source->query)) {
                $hash = $source->toHash();

                // delete query hash
                $this->_driver->delete($hash);
            }
        }
    }

    public function clear($type)
    {
        if ($type == Cache::TYPE_MODEL) {
            $this->_driver->flushAll();
        }
    }
}