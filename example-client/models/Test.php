<?php

namespace app\models;

use apexwire\restclient\ActiveRecord;

/**
 * Class Test Базовая модель для доступа по API
 * @package app\models
 *
 * @property string $id Id
 * @property string $name Название
 * @property string $status Статус
 */
class Test extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 256],
            [['name', 'status'], 'safe'],
            [['name', 'status'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            'id',
            'status',
            'name',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Id',
            'status' => 'Статус',
            'name' => 'Название',
        ];
    }


}