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

use yii\base\InvalidConfigException;
use yii\db\QueryInterface;
use yii\data\ActiveDataProvider;

/**
 * Class RestDataProvider
 * @package yii\restclient
 */
class RestDataProvider extends ActiveDataProvider
{
    /**
     * @var RestQuery the query that is used to fetch data models and [[totalCount]]
     * if it is not explicitly set.
     */
    public $query;

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }

        return (int)$this->query->count();
    }
}
