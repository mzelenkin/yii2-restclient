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

use Yii;
use yii\rest\Serializer;
use yii\db\QueryInterface;
use yii\helpers\ArrayHelper;
use yii\base\NotSupportedException;

/**
 * Class Query
 * @package apexwire\restclient
 */
class Query extends \yii\db\Query implements QueryInterface
{
    /**
     * @var array Массив дополнительных полей. Например, `['id', 'name']`.
     * @see expand()
     */
    public $expand;

    /** @type string Название модели, которая используется для поиска */
    public $searchModel;

    /**
     * @param QueryBuilder $builder
     * @return $this
     */
    public function prepare($builder)
    {
        return $this;
    }

    /**
     * @param null $db
     * @return Command
     * @throws \yii\base\InvalidConfigException
     */
    public function createCommand($db = null)
    {
        if ($db === null) {
            /** @var Connection $db */
            $db = Yii::$app->get(Connection::getDriverName());
        }

        $commandConfig = $db->getQueryBuilder()->build($this);

        return $db->createCommand($commandConfig);
    }

    /**
     * Получаем количество объектов
     *
     * @param string $q
     * @param null $db
     * @return int|string
     */
    public function count($q = '*', $db = null)
    {
        $totalCountHeader = strtolower((new Serializer)->totalCountHeader);

        return ($count = ArrayHelper::getValue($this->createCommand($db)->head(), $totalCountHeader))
            ? current($count)
            : 0;
    }

    /**
     * @inheritdoc
     */
    public function exists($db = null)
    {
        throw new NotSupportedException('exists in is not supported.');
    }

    /**
     * @inheritdoc
     * @deprecated
     */
    public function from($tables)
    {
        throw new NotSupportedException('from in is not supported.');
    }

    /**
     * @param array|string|\yii\db\Expression $condition
     * @param array $params
     * @return \apexwire\restclient\Query|\yii\db\Query
     */
    public function where($condition, $params = [])
    {
        return parent::where($condition, $params);
    }

    /**
     * @param array|string|\yii\db\Expression $columns
     * @param null $option
     * @return \apexwire\restclient\Query|\yii\db\Query
     */
    public function select($columns, $option = null)
    {
        return parent::select($columns, $option);
    }

    /**
     * Устанавливаем дополнительные поля
     * @see http://www.yiiframework.com/doc-2.0/guide-rest-resources.html#overriding-extra-fields
     * @param string|array $columns
     * @return $this
     */
    public function expand($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->expand = $columns;

        return $this;
    }
}
