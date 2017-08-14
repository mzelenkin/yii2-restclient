Yii2 Rest Client
=====

**Tools to use API as ActiveRecord for Yii2**

Use your API as ActiveRecord

## Installation

The preferred way to install this yii2-extension is through [composer](http://getcomposer.org/download/).

Either run

```sh
php composer.phar require "apexwire/yii2-restclient"
```

or add

```json
"apexwire/yii2-restclient": "*"
```

to the require section of your composer.json.

## Configuration

To use this extension, configure restclient component in your application config:

```php
    'components' => [
        'restclient' => [
            'class' => 'apexwire\restclient\Connection',
            'config' => [
                'baseUrl' => 'https://api.site.com/',
            ],
        ],
    ],
```

## Usage

Define your Model

```php
class MyModel extends \apexwire\restclient\ActiveRecord
{
    public function attributes()
    {
        return ['id', 'name', 'status'];
    }
}
```

## Debug

Connection example yii2-httpclient debug panel

```php
$config['modules']['debug'] = [
    'class' => 'yii\debug\Module',
    'panels' => [
        'httpclient' => [
            'class' => 'yii\\httpclient\\debug\\HttpClientPanel',
        ],
    ],
];
```

## License

This project is released under the terms of the BSD-3-Clause [license](LICENSE).
Read more [here](http://choosealicense.com/licenses/bsd-3-clause).

Copyright © 2016, ApexWire

## Acknowledgments

- This project is based on [Yii2 HiArt](https://github.com/hiqdev/yii2-hiart).
