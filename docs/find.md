Поиск
=====

Расширение Yii2 Rest Client поддерживает поиск по api.

## Как устроено

Реализация поиска аналогична обычному поиску. То есть необходимо реализовать модель поиска, в которой обрабатывается GET параметры.
Пример модели: [TestSearch](../example/client/models/TestSearch.php).

При поиске по параметру на клиенте, параметры поиска добавляются к api ссылке GET параметрами. Параметры передаются в массиве.

## Реализация поиска в серверной части

В Yii2 Rest пока не реализована поддержка поиска. Поэтому необходимо реализовать поиск самим.
 
Для этого необходимо:

- изменить значение prepareDataProvider в IndexAction
- создать модель для поиска, например "TestSearch" и реализовать в ней статический метод search, входным параметром которой является массив с параметрами
 
Переопределяем массив actions, изменив "index", в ApiController. Переменная $searchClass содержит полное название модели поиска. 
Если она определена включается поиск.  

```php
    public function actions()
    {
        $actions = parent::actions();

        return ArrayHelper::merge($actions, [
            'index' => [
                'class' => 'yii\rest\IndexAction',
                'modelClass' => $this->modelClass,
                'prepareDataProvider' => function ($action) {
                    $modelClass = $action->modelClass;
                    $searchClass = $this->searchClass;
                    $params = ArrayHelper::merge(
                        Yii::$app->request->get(mb_substr(mb_strrchr($searchClass, '\\'), 1), []),
                        Yii::$app->request->post(mb_substr(mb_strrchr($searchClass, '\\'), 1), [])
                    );

                    $query = ($searchClass)
                        ? $searchClass::search($params)
                        : $modelClass::find();

                    return new ActiveDataProvider([
                        'query' => $query
                    ]);
                },
            ],
        ]);
    }
```

`mb_substr(mb_strrchr($searchClass, '\\'), 1)` - Получаем из строки "app\models\TestSearch" название модели "TestSearch"

Пример функции search в модели TestSearch.

```php
public static function search($params = [])
    {
        $query = parent::find();

        $query->andFilterWhere([
            'id' => ArrayHelper::getValue($params, 'id'),
            'status' => ArrayHelper::getValue($params, 'status'),
        ]);

        $query->andFilterWhere(['like', 'name', ArrayHelper::getValue($params, 'name')]);

        return $query;
    }
```


## Важно

При реализации клиентской и сервервой части следует обратить внимание на:

- поиск. Название моделей, с помощью которых осушествляется поиск, должны быть идентичны 