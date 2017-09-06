<?php

/*
 * Tools to use API as ActiveRecord for Yii2
 *
 * @link      https://github.com/apexwire/yii2-restclient
 * @package   yii2-restclient
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2016, ApexWire
 */

namespace apexwire\restclient;

use yii\base\Component;

/**
 * Class Command class implements the API for accessing REST API.
 * @package apexwire\restclient
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
     * @var Query
     */
    public $query;

    /**
     * @var array list of arrays or json strings that become parts of a query
     */
    public $queryParts = [];

    /**
     * Command functions
     */

    /**
     * @return \yii\httpclient\Response
     */
    public function queryAll()
    {
        $url = $this->index;
        $query = is_array($this->queryParts) ? $this->queryParts : [];

        return $this->db->get($url, $query);
    }

    /**
     * @return \yii\httpclient\Response
     */
    public function queryOne()
    {
        /* @var $query RestQuery */
        $query = $this->query;

        /* @var $class ActiveRecord */
        $class = $query->modelClass;
        $pks = $class::primaryKey();

        $url = $this->index;
        if (count($pks) == 1) {
            $primaryKey = current($pks);
            if (count($this->query->where) == 1 && isset($this->query->where[$primaryKey])) {

                return $this->db->get($url . '/' . $this->query->where[$primaryKey]);
            }
        }

        $query = is_array($this->queryParts) ? $this->queryParts : [];

        return $this->db->get($url, $query);
    }

    /**
     * CURL function
     */

    /**
     * Делаем HEAD запрос
     *
     * @return \yii\httpclient\Response
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
     * @return \yii\httpclient\Response
     */
    public function insert($data = [], $options = [])
    {
        return $this->db->post($this->index, $data, $options);
    }

    /**
     * Запрос на обновление
     *
     * @param $id
     * @param array $data
     * @param array $options
     * @return \yii\httpclient\Response
     */
    public function update($id, $data = [], $options = [])
    {
        $url = $this->index . '/' . $id;

        return $this->db->put($url, $data, $options);
    }

    /**
     * Запрос на удаление
     *
     * @param $id
     * @param array $options
     * @return \yii\httpclient\Response
     */
    public function delete($id, $options = [])
    {
        $url = $this->index . '/' . $id;

        return $this->db->delete($url, $options);
    }

    /**
     * @return \yii\httpclient\Response
     */
    public function getResponse()
    {
        return $this->db->getResponse();
    }
}
