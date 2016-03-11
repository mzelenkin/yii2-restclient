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
//        $this->buildLimit($query->limit, $params);
        $this->buildPage($query->offset, $query->limit, $params);
        $params = ArrayHelper::merge($params, $this->buildOrderBy($query->orderBy));
        $params = ArrayHelper::merge($params, $this->buildCondition($query->where, $params));

        return [
            'queryParts' => $params,
            'index' => $query->index
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
    public function buildOrderBy($orderBy)
    {
        $params = [];
        if (!empty($orderBy)) {
            $params['sort'] = $this->_sort[reset($orderBy)] . key($orderBy);
        }

        return $params;
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
        static $builders = [
            'and' => 'buildAndCondition',
            'between' => 'buildBetweenCondition',
            'eq' => 'buildEqCondition',
            'in' => 'buildInCondition',
            'like' => 'buildLikeCondition',
            'gt' => 'buildGreaterThenCondition',
            'lt' => 'buildLessThanCondition',
        ];
        if (empty($condition)) {
            return [];
        }
        if (!is_array($condition)) {
            throw new NotSupportedException('String conditions in where() are not supported by HiActiveResource.');
        }

        if (isset($condition[0])) { // operator format: operator, operand 1, operand 2, ...
            $operator = strtolower($condition[0]);
            if (isset($builders[$operator])) {
                $method = $builders[$operator];
                array_shift($condition); // Shift build condition

                return $this->$method($operator, $condition);
            } else {
                throw new InvalidParamException('Found unknown operator in query: ' . $operator);
            }
        } else {
            return $this->buildHashCondition($condition, $params);
        }
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
}
