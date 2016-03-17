<?php

/*
 * Tools to use API as ActiveRecord for Yii2
 *
 * @link      https://github.com/apexwire/yii2-restclient
 * @package   yii2-restclient
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2016, ApexWire
 */

namespace yii\restclient;

use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;

/**
 * Class Command class implements the API for accessing REST API.
 * @package yii\restclient
 */
class Command extends Component
{
    /**
     * @var Connection
     */
    public $db;
    /**
     * @var string|array the indexes to execute the query on. Defaults to null meaning all indexes
     */
    public $index;

    /**
     * @var array list of arrays or json strings that become parts of a query
     */
    public $queryParts = [];

    /**
     * Command functions
     */

    /**
     * @param array $options
     * @return mixed
     */
    public function queryAll($options = [])
    {
        $url = $this->index;
        $query = is_array($this->queryParts) ? $this->queryParts : [];
        $options = ArrayHelper::merge($query, $options);

        return $this->db->get($url, $options);
    }

    /**
     * @param array $options
     * @return mixed
     */
    public function queryOne($options = [])
    {
        //TODO: use $this->getOldPrimaryKey() yii\restclient\ActiveRecord
        $url = $this->index . '/' . current($this->queryParts);

        return $this->db->get($url);
    }

    /**
     * CURL function
     */

    /**
     * Делаем HEAD запрос
     *
     * @return mixed
     */
    public function head()
    {
        $query = is_array($this->queryParts) ? $this->queryParts : [];

        return $this->db->head($this->index, $query);
    }

    /**
     * Запрос на создание
     *
     * @param array $data
     * @param array $options
     * @return mixed
     */
    public function insert($data = [], $options = [])
    {
        return $this->db->post($this->index, $options, $data);
    }

    /**
     * Запрос на обновление
     *
     * @param $id
     * @param array $data
     * @param array $options
     * @return mixed
     */
    public function update($id, $data = [], $options = [])
    {
        $url = $this->index . '/' . $id;

        return $this->db->put($url, $options, $data);
    }

    /**
     * Запрос на удаление
     *
     * @param $id
     * @param array $options
     * @return mixed
     */
    public function delete($id, $options = [])
    {
        $url = $this->index . '/' . $id;

        return $this->db->delete($url, $options);
    }
}
