To use this extension, configure restclient component in your application config:

```php
    'components' => [
        'restclient' => [
            'class' => 'yii\restclient\Connection',
            'config' => [
                'base_uri' => 'https://api.site.com/',
            ],
        ],
    ],
```
