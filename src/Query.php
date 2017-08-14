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
}
