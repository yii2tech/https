Secure connection (https) handling extension for Yii2
=====================================================

This extension provides some tools for the secure connection (https) handling.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/https/v/stable.png)](https://packagist.org/packages/yii2tech/https)
[![Total Downloads](https://poser.pugx.org/yii2tech/https/downloads.png)](https://packagist.org/packages/yii2tech/https)
[![Build Status](https://travis-ci.org/yii2tech/https.svg?branch=master)](https://travis-ci.org/yii2tech/https)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/https
```

or add

```json
"yii2tech/https": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides some tools for the secure connection (https) handling.

Filter [[\yii2tech\https\SecureConnectionFilter]] allows automatic redirection from 'http' to 'https' protocol,
depending of which one is required by particular action. Actions separation into those requiring secure protocol
and the ones requiring unsecure protocol can be setup via `secureOnly` and `secureExcept` properties.

Being descendant of [[yii\base\ActionFilter]], [[\yii2tech\https\SecureConnectionFilter]] can be setup both at the
controller level and at module (application) level.

Application configuration example:

```php
return [
    'as https' => [
        'class' => 'yii2tech\https\SecureConnectionFilter',
        'secureOnly' => [
            'site/login',
            'site/signup',
        ],
    ],
    // ...
];
```

Controller configuration example:

```php
use yii\web\Controller;
use yii2tech\https\SecureConnectionFilter;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'https' => [
                'class' => SecureConnectionFilter::className(),
                'secureOnly' => [
                    'login',
                    'signup',
                ],
            ],
        ];
    }

    // ...
}
```

**Heads up!** Do not forget about `only` and `except` properties of the filter. Keep in mind that `secureOnly`
and `secureExcept` can not affect those actions, which are excluded from filtering via `only` and `except`.
You may use this to skip some actions from the secure connection processing.

**Heads up!** Be aware of the forms, which may appear at on protocol but require submission to the other.
Request body can not be transferred during redirect, so submitted data will be lost. You'll have to setup
form action manually with the correct schema, instead of relying on the filter.
