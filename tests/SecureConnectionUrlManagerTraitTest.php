<?php

namespace yii2tech\tests\unit\https;

use Yii;
use yii2tech\https\UrlManager;

class SecureConnectionUrlManagerTraitTest extends TestCase
{
    /**
     * @param array $config component config.
     * @return UrlManager URL manager instance.
     */
    protected function mockUrlManager($config = [])
    {
        if (!isset($config['class'])) {
            $config['class'] = UrlManager::className();
        }
        $config['enablePrettyUrl'] = true;
        return Yii::createObject($config);
    }

    // Tests :

    public function testSecureOnlyRoutes()
    {
        $urlManager = $this->mockUrlManager([
            'rules' => [
                '/' => 'site/index',
                'login' => 'auth/login',
            ],
            'secureOnlyRoutes' => [
                'auth/login'
            ]
        ]);

        $this->assertEquals('https://domain.com/index.php/login', $urlManager->createUrl(['auth/login']));
        $this->assertEquals('/index.php/', $urlManager->createUrl(['site/index']));
    }

    public function testSecureExceptRoutes()
    {
        $_SERVER['HTTPS'] = 'on';
        Yii::$app->request->setHostInfo('https://domain.com');

        $urlManager = $this->mockUrlManager([
            'rules' => [
                '/' => 'site/index',
                'login' => 'auth/login',
            ],
            'secureExceptRoutes' => [
                'site/index'
            ]
        ]);

        $this->assertEquals('/index.php/login', $urlManager->createUrl(['auth/login']));
        $this->assertEquals('http://domain.com/index.php/', $urlManager->createUrl(['site/index']));
    }
}