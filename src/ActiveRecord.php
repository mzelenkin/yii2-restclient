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

use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;
use yii\db\BaseActiveRecord;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\NotFoundHttpException;

/**
 * Class ActiveRecord
 * @package apexwire\restclient
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     * @return Connection|object|null
     * @throws InvalidConfigException
     */
    public static function getDb()
    {
        return \Yii::$app->get(Connection::getDriverName());
    }

    /**
     * @inheritdoc
     *
     * @return RestQuery|\yii\db\ActiveQueryInterface|object
     */
    public static function find($options = [])
    {
        $config = [
            'class' => RestQuery::className(),
            'options' => $options,
        ];

        return \Yii::createObject($config, [get_called_class()]);
    }

    /**
     * @inheritdoc
     */
    public static function findAll($condition, $options = [])
    {
        return static::find($options)->andWhere($condition)->all();
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['id'];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        throw new InvalidConfigException('The attributes() method of RestClient ActiveRecord has to be implemented by child classes.');
    }

    /**
     * @return string the name of the index this record is stored in.
     */
    public static function modelName()
    {
        return Inflector::pluralize(Inflector::camel2id(StringHelper::basename(get_called_class()), '-'));
    }

    /**
     * @inheritdoc
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            return false;
        }

        if (!$this->beforeSave(true)) {
            return false;
        }

        $values = $this->getDirtyAttributes($attributes);
        $command = static::getDb()->createCommand(['index' => static::modelName()]);
        $result = $command->insert($values);
        $response = $command->getResponse();

        if ($response->isOk) {
            $this->setAttributes($result);
            $pk = static::primaryKey()[0];
            $this->$pk = $result['id'];
            if ($pk !== 'id') {
                $values[$pk] = $result['id'];
            }
            $changedAttributes = array_fill_keys(array_keys($values), null);
            $this->setOldAttributes($values);
            $this->afterSave(true, $changedAttributes);

            return true;
        }

        switch ($response->getStatusCode()) {
            case 422:
                foreach ($result as $error) {
                    $this->addError($error['field'], $error['message']);
                }
                break;

        }

        return false;
    }

    /**
     * @inheritdoc
     */
    protected function updateInternal($attributes = null)
    {
        if (!$this->beforeSave(false)) {
            return false;
        }

        $values = $this->getAttributes($attributes);

        if (empty($values)) {
            $this->afterSave(false, $values);

            return 0;
        }

        $command = static::getDb()->createCommand(['index' => static::modelName()]);
        $result = $command->update(
            $this->getOldPrimaryKey(),
            $values
        );
        $response = $command->getResponse();

        if ($response->isOk) {
            $changedAttributes = [];
            foreach ($values as $name => $value) {
                $changedAttributes[$name] = $this->getOldAttribute($name);
                $this->setOldAttribute($name, $value);
            }

            $this->afterSave(false, $changedAttributes);

            //TODO проверить возвращаемое значение
            return $result;
        }

        switch ($response->getStatusCode()) {
            case 422:
                foreach ($result as $error) {
                    $this->addError($error['field'], $error['message']);
                }
                break;

        }

        return 0;
    }

    /**
     * //TODO проверить удаление записи
     * @inheritdoc
     */
    public function delete($options = [])
    {
        $result = false;
        if ($this->beforeDelete()) {
            $command = static::getDb()->createCommand(['index' => static::modelName()]);
            $result = $command->delete(
                $this->getOldPrimaryKey(),
                $options
            );
            $response = $command->getResponse();

            if ($response->isOk) {
                $this->setOldAttributes(null);
                $this->afterDelete();

                return true;
            }

            switch ($response->getStatusCode()) {
                case 404:
                    throw new NotFoundHttpException('Страница для удаления не найдена.');
                    break;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getIsNewRecord()
    {
        return !$this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function unlinkAll($name, $delete = false)
    {
        throw new NotSupportedException('unlinkAll() is not supported by RestClient, use unlink() instead.');
    }

    /**
     * @inheritdoc
     * @throws \yii\base\UnknownPropertyException
     */
    public static function populateRecord($record, $row)
    {
        $attributes = array_flip($record->attributes());
        foreach ($attributes as $attributeName => $attributeValue) {
            if (!array_key_exists($attributeName, $row)) {
                throw new UnknownPropertyException("Attribute `{$attributeName}` not found in API response. Available fields: " . implode(', ', array_keys($row)) . '.');
            }
        }
        parent::populateRecord($record, $row);
    }
}
