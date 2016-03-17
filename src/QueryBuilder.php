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

use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;

/**
 * Class QueryBuilder builds an HiActiveResource query based on the specification given as a [[Query]] object.
 * @package yii\restclient
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @type array
     */
    private $_sort = [
        SORT_ASC => '',
        SORT_DESC => '-',
    ];

    /**
     * @param RestQuery $query
     * @param array $params
     * @return array
     * @throws NotSupportedException
     */
    public function build($query, $params = [])
    {
        $query->prepare($this);

        $this->buildSelect($query->select, $params);
        $this->buildPerPage($query->limit, $params);
        $this->buildPage($query->offset, $query->limit, $params);
        $this->buildFind($query->where, $query->searchModel, $params);
        $this->buildSort($query->orderBy, $params);

        return [
            'queryParts' => $params,
            'index' => $query->from
        ];
    }

    /**
     * @inheritdoc
     */
    public function buildLimit($limit, $offset)
    {
        throw new NotSupportedException('buildLimit in is not supported.');
    }

    /**
     * Устанавливаем количество записей на страницу
     *
     * @param $limit
     * @param $params
     */
    public function buildPerPage($limit, &$params)
    {
        if (is_int($limit)) {
            $params['per-page'] = $limit;
        }
    }

    /**
     * @param $offset
     * @param $limit
     * @param $params
     */
    public function buildPage($offset, $limit, &$params)
    {
        if ($offset > 0) {
            $params['page'] = ceil($offset / $limit) + 1;
        }
    }

    /**
     * @inheritdoc
     */
    public function buildOrderBy($columns)
    {
        throw new NotSupportedException('buildOrderBy in is not supported.');
    }

    /**
     * Преобразуем массив параметров where в массив для поиска
     *
     * @param $condition
     * @param $searchModel
     * @param $params
     */
    public function buildFind($condition, $searchModel, &$params)
    {
        if (!empty($condition) && is_array($condition)) {

            foreach ($condition as $label => $value) {
                $params[$searchModel . '[' . $label . ']'] = $value;
                unset($params[$label]);
            }
        }
    }

    /**
     * Устанавливаем параметр сортировки
     *
     * @param $orderBy
     * @param $params
     * @return array
     */
    public function buildSort($orderBy, &$params)
    {
        if (!empty($orderBy)) {
            $params['sort'] = $this->_sort[reset($orderBy)] . key($orderBy);
        }
    }

    /**
     * @inheritdoc
     */
    public function buildSelect($columns, &$params, $distinct = false, $selectOption = null)
    {
        if (!empty($columns) AND is_array($columns)) {
            $params['fields'] = implode(',', $columns);
        }
    }

    /**
     * @inheritdoc
     */
    public function buildCondition($condition, &$params)
    {
        throw new NotSupportedException('buildCondition in is not supported.');
    }

    /**
     * @inheritdoc
     */
    public function buildHashCondition($condition, &$params)
    {
        //TODO: проверить работу
        $parts = [];
        foreach ($condition as $attribute => $value) {
            if (is_array($value)) { // IN condition
                $parts[$attribute . 's'] = implode(',', $value);
            } else {
                $parts[$attribute] = $value;
            }
        }

        return $parts;
    }

    /**
     * @inheritdoc
     */
    public function buildLikeCondition($operator, $operands, &$params)
    {
        throw new NotSupportedException('buildLikeCondition in is not supported.');
    }

    /**
     * @inheritdoc
     */
    public function buildAndCondition($operator, $operands, &$params)
    {
        throw new NotSupportedException('buildAndCondition in is not supported.');
    }

    /**
     * @inheritdoc
     */
    public function buildBetweenCondition($operator, $operands, &$params)
    {
        throw new NotSupportedException('buildBetweenCondition is not supported.');
    }

    /**
     * @inheritdoc
     */
    public function buildInCondition($operator, $operands, &$params)
    {
        throw new NotSupportedException('buildInCondition in is not supported.');
    }

    /**
     * @inheritdoc
     */
    protected function buildCompositeInCondition($operator, $columns, $values, &$params)
    {
        throw new NotSupportedException('buildCompositeInCondition in is not supported.');
    }

    public function buildWhere($condition, &$params)
    {
        throw new NotSupportedException('buildWhere in is not supported.');
    }
}
