Define your Model

```php
class MyModel extends \yii\restclient\ActiveRecord
{
    public function attributes()
    {
        return ['id', 'name', 'else'];
    }
}
```

