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


## Automatic URL creation <span id="automatic-url-creation"></span>

Using simple redirect from one protocol to another is not efficient and have a risk of loosing data submitted via
web form. Thus it is better to explicitly specify URL with correct protocol in your views.
You may simplify this process using [[\yii2tech\https\SecureUrlRuleFilter]] action filter. Once applied it will adjust
[[\yii\web\UrlManager::rules]] in the way [[\yii\web\UrlManager::createUrl()]] method will automatically create
absolute URL with correct protocol in case it miss matches current one.

Application configuration example:

```php
return [
    'as secureUrlRules' => [
        'class' => 'yii2tech\https\SecureUrlRuleFilter',
        'secureOnlyRoutes' => [
            'auth/login',
            'site/signup',
        ],
        'secureExceptRoutes' => [
            'site/index',
            'help/<action>',
        ],
    ],
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/index',
                'login' => 'auth/login',
                'signup' => 'site/signup',
                '<action:contact|faq>' => 'help/<action>',
            ]
        ],
    ],
    // ...
];
```

Now [[\yii\web\UrlManager::createUrl()]] will create URLs with correct protocol without extra efforts:

```php
if (Yii::$app->request->isSecureConnection) {
    echo Yii::$app->urlManager->createUrl(['site/index']); // outputs: 'http://domain.com/'
    echo Yii::$app->urlManager->createUrl(['auth/login']); // outputs: '/login'
} else {
    echo Yii::$app->urlManager->createUrl(['site/index']); // outputs: '/'
    echo Yii::$app->urlManager->createUrl(['auth/login']); // outputs: 'https://domain.com/login'
}
```

> Note: [[\yii2tech\https\SecureUrlRuleFilter]] filter will take affect only if
  [[\yii\web\UrlManager::enablePrettyUrl]] is enabled.

**Heads up!** once applied [[\yii2tech\https\SecureUrlRuleFilter]] filter changes the state of related
[[\yii\web\UrlManager::UrlManager]] instance, which may make unexpected side effects. For example: this may
break such features as parsing URL.
