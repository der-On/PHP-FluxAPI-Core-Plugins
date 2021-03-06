<?php
namespace Plugins\FluxAPI\Core\Storage;

use \FluxAPI\Query;
use \FluxAPI\Field;
use \Doctrine\DBAL\Query\QueryBuilder;
use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\Comparator;

class MySql extends \FluxAPI\Storage
{
    public function __construct(\FluxAPI\Api $api, array $config = array())
    {
        parent::__construct($api, $config);

        if (!\Doctrine\DBAL\Types\Type::hasType('varbinary')) {
            \Doctrine\DBAL\Types\Type::addType('varbinary', 'Plugins\FluxAPI\Core\DbalTypeVarbinary');
        }
    }


    public function addFilters()
    {
        parent::addFilters();

        $this->addFilter('join','filterJoin');
    }

    protected function _where(&$qb, $expr)
    {
        if ($this->isFilterOr()) {
            $qb->orWhere($expr);
        }
        else {
            $qb->andWhere($expr);
        }
    }

    public function filterSelect(&$qb, array $params)
    {
        $isId = ($params[0] == 'id');

        if ($isId) {
            $params[1] = $this->uuidToHex($params[1]);
        }

        $qb->select($params);
        return $qb;
    }

    public function filterRaw(&$qb, array $params)
    {
        $qb->andWhere($params[0]);
    }

    public function filterEqual(&$qb, array $params)
    {
        $isField = (isset($params[2]) && $params[2] == 'field');
        $isId = (!$isField && $params[0] == 'id' || substr($params[0],-3) == '_id');

        if (!$isField && $isId) {
            $params[1] = $this->uuidToHex($params[1]);
            $type = 'varbinary';
        } else {
            $type = (isset($params[2]))?$params[2]:'string';
        }

        $this->_where($qb, $qb->expr()->eq($params[0], ($type!='string') ? $params[1] : $qb->expr()->literal($params[1])));

        return $qb;
    }

    public function filterNotEqual(&$qb, array $params)
    {
        $isField = (isset($params[2]) && $params[2] == 'field');
        $isId = (!$isField && $params[0] == 'id' || substr($params[0],-3) == '_id');

        if (!$isField && $isId) {
            $params[1] = $this->uuidToHex($params[1]);
            $type = 'varbinary';
        } else {
            $type = (isset($params[2]))?$params[2]:'string';
        }

        $this->_where($qb, $qb->expr()->neq($params[0],($type!='string')?$params[1]:$qb->expr()->literal($params[1])));

        return $qb;
    }

    public function filterGreaterThen(&$qb, array $params)
    {
        if (is_numeric($params[1])) {
            $this->_where($qb, $qb->expr()->gt($params[0], $params[1]));
        } else {
            $this->_where($qb, $qb->expr()->gt($params[0], $qb->expr()->literal($params[1])));
        }

        return $qb;
    }

    public function filterGreaterThenOrEqual(&$qb, array $params)
    {
        if (is_numeric($params[1])) {
            $this->_where($qb, $qb->expr()->gte($params[0], $params[1]));
        } else {
            $this->_where($qb, $qb->expr()->gte($params[0], $qb->expr()->literal($params[1])));
        }
        return $qb;
    }

    public function filterLessThen(&$qb, array $params)
    {
        if (is_numeric($params[1])) {
            $this->_where($qb, $qb->expr()->lt($params[0], $params[1]));
        } else {
            $this->_where($qb, $qb->expr()->lt($params[0], $qb->expr()->literal($params[1])));
        }
        return $qb;
    }

    public function filterLessThenOrEqual(&$qb, array $params)
    {
        if (is_numeric($params[1])) {
            $this->_where($qb, $qb->expr()->lte($params[0], $params[1]));
        } else {
            $this->_where($qb, $qb->expr()->lte($params[0], $qb->expr()->literal($params[1])));
        }
        return $qb;
    }

    public function filterRange(&$qb, array $params)
    {
        $this->_where($qb, $qb->expr()->andX(
            $qb->expr()->gte($params[0],$params[1]),
            $qb->expr()->lte($params[0],$params[2])
        ));
        return $qb;
    }

    public function filterOrder(&$qb, array $params)
    {
        $qb->addOrderBy($params[0],isset($params[1])?$params[1]:'ASC');
        return $qb;
    }

    public function filterGroup(&$qb, array $params)
    {
        $qb->add('groupBy', $params[0] . ' ' . ((isset($params[1]) && $params[1] != Field::ORDER_NONE)? $params[1] : ''));
        return $qb;
    }

    public function filterLimit(&$qb, array $params)
    {
        $qb->setFirstResult(intval($params[0]));
        $qb->setMaxResults(intval($params[1]));
        return $qb;
    }

    public function filterCount(&$qb, array $params)
    {
        $qb->select('COUNT('.$params[0].')');
        return $qb;
    }

    public function filterLike(&$qb, array $params)
    {
        $this->_where($qb, $qb->expr()->like($params[0], $qb->expr()->literal($params[1])));
        return $qb;
    }

    public function filterIn(&$qb, array $params)
    {
        $values = $params[1];

        $in = '';

        if (!is_array($values)) {
            $values = explode(',',$values);
        }

        foreach($values as $i => $value) {
            $in .= $qb->expr()->literal($value);

            if ($i < count($values) -1) {
                $in .= ', ';
            }
        }

        $this->_where($qb, $params[0].' IN ('.$in.')');
        return $qb;
    }

    public function filterDistinct(&$qb, array $params)
    {
        $qb->select('DISTINCT  ' . $params[0]);
        return $qb;
    }

    public function filterJoin(&$qb, array $params)
    {
        $_params = $params;
        array_shift($_params);

        switch($params[0]) {
            case 'inner':
                return $this->filterInnerJoin($qb,$_params);
                break;

            case 'left':
                return $this->filterLeftJoin($qb,$_params);
                break;

            default:
                return $qb;
        }
    }

    public function filterInnerJoin(&$qb, array $params)
    {
        return $qb;
    }

    public function filterLeftJoin(&$qb, array $params)
    {
        $qb->leftJoin($params[0],$params[1], $params[1], $params[2]);
        return $qb;
    }

    public function isConnected()
    {
        return isset($this->_api->app['db']);
    }

    public function connect()
    {
        $this->_api->app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
            'db.options' => array(
                'driver' => 'pdo_mysql',
                'host' => $this->config['host'],
                'user' => $this->config['user'],
                'password' => $this->config['password'],
                'dbname' => $this->config['database'],
                'charset' => 'UTF8',
                'debug_sql' => FALSE,
            ),
        ));
    }

    public function getConnection()
    {
        return $this->_api->app['db'];
    }

    public function getLastId($model_name)
    {
        $connection = $this->getConnection();
        $table_name = $this->getTableName($model_name);

        $sql = 'SELECT LAST_INSERT_ID() FROM '.$table_name;

        if ($this->config['debug_sql']) {
            print("\nSQL: ".$sql."\n");
        }

        $result = $connection->query($sql)->fetch();

        return $result['LAST_INSERT_ID()'];
    }

    protected function _getLoadRelationQuery(\FluxAPI\Model $model, $name)
    {
        $field = $model->getField($name);

        $id = $model->id;
        $model_name = $model->getModelName();
        $rel_model_name = $field->relationModel;
        $foreign_table_name = $this->getTableName($rel_model_name); // name of the foreign model table

        $query = new Query();

        // look for ids in own relations table
        if (in_array($field->relationType, array(Field::HAS_MANY, Field::HAS_ONE))) {
            $id_field_name = strtolower($model_name) . '_id'; // own ID field in own relations table
            $rel_table_name = $this->getRelationTableName($model_name); // name of own relations table

            $query
                ->filter('join',array('left', $foreign_table_name, $rel_table_name, $rel_table_name . '.field="' . $field->name . '" AND ' . $rel_table_name . '.' . $id_field_name . '=' . $this->uuidToHex($id)))
                ->filter('equal',array('id' , $rel_table_name . '.foreign_id', 'field'))
            ;
        }
        // look for ids in foreign relations table
        elseif (in_array($field->relationType, array(Field::BELONGS_TO_MANY, Field::BELONGS_TO_ONE))) {
            $foreign_rel_table_name = $this->getRelationTableName($rel_model_name); // name of the foreign relations table
            $foreign_id_field_name = strtolower($rel_model_name) . '_id';

            $query
                ->filter('join',array('left', $foreign_table_name, $foreign_rel_table_name, $foreign_rel_table_name . '.field="' . $field->relationField . '" AND ' . $foreign_rel_table_name . '.foreign_id=' . $this->uuidToHex($id)))
                ->filter('equal',array('id' , $foreign_rel_table_name . '.' . $foreign_id_field_name, 'field'))
            ;
        }

        // add ordering if any
        if ($field->relationOrder && is_array($field->relationOrder)) {
            foreach($field->relationOrder as $key => $sort) {
                if (in_array($sort, array(Field::ORDER_ASC, Field::ORDER_DESC))) {
                    $query->filter('order', array($key, $sort));
                }
            }
        }

        // add grouping if any
        if ($field->relationGroup && is_array($field->relationGroup)) {
            foreach($field->relationGroup as $key => $sort) {
                if (in_array($sort, array(Field::ORDER_ASC, Field::ORDER_DESC, Field::ORDER_NONE))) {
                    $query->filter('group', array($key, $sort));
                }
            }
        }

        $query->setType(Query::TYPE_SELECT);
        $query->setModelName($rel_model_name);

        return $query;
    }

    public function loadRelation(\FluxAPI\Model $model, $name)
    {
        if (!$model->hasField($name)) {
            return NULL;
        } else {
            $field = $model->getField($name);
        }

        if ($field->type == Field::TYPE_RELATION && !empty($field->relationModel)) {
            $query = $this->_getLoadRelationQuery($model, $name);
            $models = $this->_api->load($field->relationModel, $query);

            if (in_array($field->relationType,array(Field::BELONGS_TO_ONE, Field::HAS_ONE))) {
                if ($models->count() > 0) {
                    return $models[0];
                } else {
                    return null;
                }
            } else {
                return $models;
            }
        }
        return NULL;
    }

    public function addRelation(\FluxAPI\Model $model, \FluxAPI\Model $relation, \FluxAPI\Field $field)
    {
        $connection = $this->getConnection();

        $model_name = $model->getModelName();
        $rel_model_name = $field->relationModel;
        $rel_table_name = $this->getRelationTableName($model_name); // get the table name of the relations table

        // before a new record is inserted we need to check if it's not related already
        if (in_array($field->relationType, array(Field::HAS_MANY, Field::HAS_ONE))) {
            $id_field_name = $this->getCollectionName($model_name).'_id'; // own ID field in own relations table

            $sql = 'SELECT COUNT(foreign_id) FROM ' . $rel_table_name . ' WHERE ' . $id_field_name . '=' . $this->uuidToHex($model->id) . ' AND field = "' . $field->name . '" AND foreign_id =' . $this->uuidToHex($relation->id);
            $result = $connection->query($sql)->fetch();
            $count = intval($result['COUNT(foreign_id)']);
        }
        elseif (in_array($field->relationType, array(Field::BELONGS_TO_MANY, Field::BELONGS_TO_ONE))) {
            $foreign_rel_table_name = $this->getRelationTableName($rel_model_name); // foreign relations table name
            $foreign_id_field_name = $this->getCollectionName($rel_model_name) . '_id'; // foreign ID field in foreign relations table

            $sql = 'SELECT COUNT(foreign_id) FROM ' . $foreign_rel_table_name . ' WHERE foreign_id = ' . $this->uuidToHex($model->id) . ' AND field = "' . $field->relationField . '" AND ' . $foreign_id_field_name . '=' . $this->uuidToHex($relation->id);
            $result = $connection->query($sql)->fetch();
            $count = intval($result['COUNT(foreign_id)']);
        }

        if ($count == 0) {
            if (in_array($field->relationType, array(Field::HAS_MANY, Field::HAS_ONE))) {
                $sql = 'INSERT INTO ' . $rel_table_name . ' (' . $id_field_name . ',field,foreign_id) VALUES(' . $this->uuidToHex($model->id) . ',"' . $field->name . '",' . $this->uuidToHex($relation->id) . ')';
            }
            elseif (in_array($field->relationType, array(Field::BELONGS_TO_MANY, Field::BELONGS_TO_ONE))) {
                $sql = 'INSERT INTO ' . $foreign_rel_table_name . ' (' . $foreign_id_field_name . ',field,foreign_id) VALUES(' . $this->uuidToHex($relation->id) . ',"' . $field->relationField . '",' . $this->uuidToHex($model->id) . ')';
            }

            if ($this->config['debug_sql']) {
                print("\nSQL: ".$sql."\n");
            }

            $connection->query($sql);
        }
    }

    public function removeCachedRelationModels(\FluxAPI\Model $model, $name)
    {
        $query = $this->_getLoadRelationQuery($model, $name);
        $this->removeCachedModels($model->getModelName(), $query);
    }

    public function removeRelation(\FluxAPI\Model $model, \FluxAPI\Model $relation, \FluxAPI\Field $field)
    {
        $connection = $this->getConnection();

        $model_name = $model->getModelName();

        if (in_array($field->relationType, array(Field::HAS_MANY, Field::HAS_ONE))) {
            $rel_table_name = $this->getRelationTableName($model_name); // own relations table
            $id_field_name = $this->getCollectionName($model_name) . '_id'; // own ID field in own relations table

            $sql = 'DELETE FROM ' . $rel_table_name . ' WHERE ' . $id_field_name . '=' . $this->uuidToHex($model->id) . ' AND field = "' . $field->name .'" AND foreign_id = ' . $this->uuidToHex($relation->id);
        }
        elseif (in_array($field->relationType, array(Field::BELONGS_TO_MANY, Field::BELONGS_TO_ONE))) {
            $foreign_id_field_name = $this->getCollectionName($field->relationModel) . '_id'; // foreign ID field in foreign relations table
            $foreign_rel_table_name = $this->getRelationTableName($field->relationModel); // foreign relations table

            $sql = 'DELETE FROM ' . $foreign_rel_table_name . ' WHERE ' . $foreign_id_field_name . '=' . $this->uuidToHex($relation->id) . ' AND field = "' . $field->relationField . '" AND foreign_id = ' . $this->uuidToHex($model->id);
        }

        if ($this->config['debug_sql']) {
            print("\nSQL: ".$sql."\n");
        }

        $connection->query($sql);
    }

    public function removeAllRelations(\FluxAPI\Model $model, \FluxAPI\Field $field, array $exclude_ids = array())
    {
        $model_name = $model->getModelName();

        $connection = $this->getConnection();

        if (in_array($field->relationType, array(Field::HAS_MANY, Field::HAS_ONE))) {
            $rel_table = $this->getRelationTableName($model_name); // own relations table
            $id_field_name = $this->getCollectionName($model_name) . '_id'; // own ID field in own relations table

            $sql = 'DELETE FROM ' . $rel_table . ' WHERE ' . $id_field_name . '=' . $this->uuidToHex($model->id) . ' AND field = "' . $field->name  . '"';

            foreach($exclude_ids as $i => $id) {
                $exclude_ids[$i] = $this->uuidToHex($id);
            }

            if (count($exclude_ids) > 0) {
                $sql .= ' AND foreign_id NOT IN (' . implode(',', $exclude_ids) . ')';
            }
        }
        elseif (in_array($field->relationType, array(Field::BELONGS_TO_MANY, Field::BELONGS_TO_ONE))) {
            $foreign_rel_table_name = $this->getRelationTableName($field->relationModel); // foreign relations table
            $foreign_id_field_name = $this->getCollectionName($field->relationModel) . '_id'; // foreign ID field in foreign relations table

            $sql = 'DELETE FROM ' . $foreign_rel_table_name . ' WHERE field = "' . $field->relationField . '" AND foreign_id = ' . $this->uuidToHex($model->id);

            foreach($exclude_ids as $i => $id) {
                $exclude_ids[$i] = $this->uuidToHex($id);
            }

            if (count($exclude_ids) > 0) {
                $sql .= ' AND ' . $foreign_id_field_name . ' NOT IN (' . implode(',', $exclude_ids) . ')';
            }
        }

        if ($this->config['debug_sql']) {
            print("\nSQL: ".$sql."\n");
        }

        $connection->query($sql);
    }

    public function getTableName($model_name)
    {
        return $this->config['table_prefix'].$this->getCollectionName($model_name);
    }

    public function getRelationTableName($model_name)
    {
        return $this->config['table_prefix'].$this->getCollectionName($model_name).'_rel';
    }

    public function executeQuery(\FluxAPI\Query $query)
    {
        parent::executeQuery($query);

        $model_name = $query->getModelName();

        $this->_api['permissions']->setAccessOverride(TRUE, 'Model', $model_name, 'create');
        $model = $this->_api['models']->create($model_name);

        $this->_api['permissions']->unsetAccessOverride('Model', $model_name, 'create');

        $fields = $model->getFields();
        $tableName = $this->getTableName($model_name);

        $connection = $this->getConnection();
        $qb = $connection->createQueryBuilder();

        if ($query->getType() == Query::TYPE_INSERT) { // Doctrines query builder does not support INSERTs so we need to create the SQL manually
            $data = $query->getData();

            // serialize and remove empty fields
            foreach($data as $name => $value) {
                if (empty($value)) {
                    unset($data[$name]);
                } elseif (isset($fields[$name]) && $fields[$name]->type != Field::TYPE_RELATION) {
                    $data[$name] = $this->serialize($data[$name], $fields[$name]);
                }
            }

            $sql = 'INSERT INTO '.$tableName
                .' ('
                    .implode(', ',array_keys($data))
                .') VALUES(';

                $i = 0;
                foreach($data as $name => $value) {
                    // convert uuids to hex
                    if ($name == 'id') {
                        $value = $this->uuidToHex($value);
                        $sql .= $value;
                    } else {
                        $sql .= $qb->expr()->literal($value);
                    }

                    if ($i < count($data) - 1) {
                        $sql .= ',';
                    }
                    $i++;
                }
                $sql .= ')';

            if ($this->config['debug_sql']) {
                print("\nSQL: ".$sql."\n");
            }

            $connection->query($sql);
            return TRUE;
        } else {

            if ($query->getType() == Query::TYPE_COUNT) {
                $query->filter('count',array('id'));
            }

            switch($query->getType()) {
                case Query::TYPE_SELECT:
                    $qb->select('*'); // by default select all fields
                    $qb->from($tableName, $tableName);
                    break;

                case Query::TYPE_DELETE:
                    $qb->delete($tableName);
                    break;

                case Query::TYPE_UPDATE:
                    $qb->update($tableName);

                    foreach($query->getData() as $name => $value)
                    {
                        if ($name != 'id' && isset($fields[$name]) && $fields[$name]->type != Field::TYPE_RELATION) { // do not set the ID again
                            $value = $this->serialize($value, $fields[$name]);
                            $qb->set($name,$qb->expr()->literal($value));
                        }
                    }
                    break;

                case Query::TYPE_COUNT:
                    $qb->from($tableName,$tableName);
                    break;
            }

            // apply query filters
            $queryFilters = $query->getFilters();

            foreach($queryFilters as $filter) {
                if ($this->hasFilter($filter[0])) {
                    $callback = $this->getFilter($filter[0]);
                    $this->executeFilter($callback,array(&$qb,$filter[1]));
                }
            }

            if ($this->config['debug_sql']) {
                print("\nSQL: ".$qb->getSQL()."\n");
            }

            $result = $qb->execute();

            if (!is_object($result)) {
                return (intval($result) > 0) ? TRUE : FALSE;
            }
            $result = $result->fetchAll();

            if ($query->getType() == Query::TYPE_COUNT) {
                return intval($result[0]['COUNT(id)']);
            } else {
                $models = new \FluxAPI\Collection\ModelCollection();

                // temporary allow everything
                $this->_api['permissions']->setAccessOverride(TRUE, 'Model', $model_name, 'create');

                foreach($result as $data) {
                    // unserialize the data
                    foreach($data as $name => $value) {
                        if (isset($fields[$name]) && $fields[$name]->type != Field::TYPE_RELATION) {
                            $data[$name] = $this->unserialize($data[$name], $fields[$name]);
                        }
                    }

                    $models->push($this->_api['models']->create($model_name, $data, NULL, FALSE));
                }

                // and reset access control
                $this->_api['permissions']->unsetAccessOverride('Model', $model_name, 'create');

                return $models;
            }
        }

        return NULL;
    }

    public function executeRawQuery($query)
    {
        parent::executeRawQuery($query);

        $connection = $this->getConnection();

        $result = $connection->query($query);

        return $result->fetchAll();
    }

    public function getFieldType(\FluxAPI\Field $field)
    {
        switch($field->type) {
            case Field::TYPE_LONGSTRING:
                $type = 'text';
                break;

            case Field::TYPE_TIMESTAMP:
                $type = 'integer';
                break;

            case Field::TYPE_BYTEARRAY:
                $type = 'varbinary';
                break;

            default:
                $type = $field->type;
        }

        return $type;
    }

    public function getFieldConfig(\FluxAPI\Field $field)
    {
        $config = array();

        if (!empty($field->length)) {
            $config['length'] = $field->length;
        }

        if ($field->unsigned) {
            $config['unsigned'] = $field->unsigned;
        }

        if ($field->autoIncrement) {
            $config['autoincrement'] = $field->autoIncrement;
        }

        return $config;
    }

    public function getRelationField(\FluxAPI\Field $field)
    {
        $rel_model_instance = $this->_api['models']->create($field->relationModel);

        if (!empty($rel_model_instance)) {
            // we need the id field of the model so we can create a field in the relation table matching the field config
            $rel_id_field = $rel_model_instance->getField('id');

            return $rel_id_field;
        } else {
            return NULL;
        }
    }

    public function migrate($model_name = NULL)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $connection = $this->getConnection();

        $toSchema = new Schema();

        $models = $this->_api['plugins']->getPlugins('Model');

        foreach($models as $model_name => $modelClass) {
            $this->_api['permissions']->setAccessOverride(TRUE, 'Model', $model_name, 'create');

            $model = $this->_api['models']->create($model_name);

            $this->_api['permissions']->unsetAccessOverride('Model', $model_name, 'create');
            $table_name = $this->getTableName($model_name);
            $table = $toSchema->createTable($table_name);
            $primary = array();
            $unique = array();

            // create relation table for this model
            $relation_table = $toSchema->createTable($this->getRelationTableName($model_name));

            // TODO: split this into multiple methods

            foreach($model->getFields() as $field) {
                if (!empty($field->name) && !empty($field->type)) {

                    if ($field->type != Field::TYPE_RELATION) {

                        $type = $this->getFieldType($field);
                        $config = $this->getFieldConfig($field);

                        $table->addColumn($field->name,$type,$config);

                        // add own model id field to relation table
                        if ($field->name == 'id') {
                            $relation_field_name = $this->getCollectionName($model_name).'_id';

                            // autoincrement must be removed
                            if (isset($config['autoincrement'])) {
                                unset($config['autoincrement']);
                            }

                            $relation_table->addColumn($relation_field_name, $type, $config);
                        }

                        if ($field->primary) {
                            $primary[] = $field->name;
                        }
                    }
                    /*// add relation model id field to relation table
                    elseif ($field->type == Field::TYPE_RELATION  &&
                            !empty($field->relationModel) &&
                            in_array($field->relationType, array(Field::HAS_MANY, Field::HAS_ONE))) {
                        // we need the id field of the related model so we can create a matching field in the relation table
                        $rel_id_field = $this->getRelationField($field);

                        if ($rel_id_field) {
                            $relation_field_name = $field->name.'_id';

                            $rel_field_type = $this->getFieldType($rel_id_field);
                            $rel_field_config = $this->getFieldConfig($rel_id_field);

                            // autoincrement must be removed
                            if (isset($rel_field_config['autoincrement'])) {
                                unset($rel_field_config['autoincrement']);
                            }

                            $relation_table->addColumn($relation_field_name, $rel_field_type, $rel_field_config);
                        }
                    }*/
                }

                if (count($primary) > 0) {
                    $table->setPrimaryKey($primary);
                }

                if (count($unique) > 0) {
                    $table->addUniqueIndex($unique);
                }
            }

            // relation field name
            $relation_table->addColumn('field', Field::TYPE_STRING, array(
                'length' => 64
            ));

            // foreign id
            $relation_table->addColumn('foreign_id', 'varbinary', array(
                'length' => 16
            ));
        }

        $comparator = new Comparator();
        $sm = $connection->getSchemaManager();
        $dp = $connection->getDatabasePlatform();
        $fromSchema = $sm->createSchema();

        $schemaDiff = $comparator->compare($fromSchema,$toSchema);
        $sql = $schemaDiff->toSql($dp);

        foreach($sql as $query) {
            if ($this->config['debug_sql']) {
                print("\nSQL: ".$query."\n");
            }
            $connection->query($query);
        }
    }

    public function unserialize($str, \FluxAPI\Field $field)
    {
        // restore truncated uuid's
        if ($field->name == 'id' && $field->type == Field::TYPE_BYTEARRAY) {
            return $this->hexToUuid($str);
        } else {
            return parent::unserialize($str, $field);
        }
    }

    public function uuidToHex($uuid)
    {
        return '0x' . str_replace('-','',$uuid);
    }

    public function hexToUuid($hex)
    {
        $hex = bin2hex($hex);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
