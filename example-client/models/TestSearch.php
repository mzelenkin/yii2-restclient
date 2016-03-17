<?php

namespace app\models;

use yii\restclient\RestDataProvider;

/**
 * Class TestSearch Модель для поиска
 * @package app\models
 */
class TestSearch extends Test
{
    public function rules()
    {
        return [
            [['id', 'name', 'status'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Test::find();
        $dataProvider = new RestDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}