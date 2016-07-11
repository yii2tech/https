<?php

namespace yii2tech\tests\unit\https;

use Yii;
use yii\base\Action;
use yii\web\Controller;
use yii\web\UrlManager;
use yii2tech\https\SecureUrlRuleFilter;

class SecureUrlRuleFilterTest extends TestCase
{
    /**
     * @return Action test action instance.
     */
    protected function mockAction()
    {
        $controller = new Controller('site', Yii::$app);
        return new Action('test', $controller);
    }

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

    public function testIsEnabled()
    {
        $filter = new SecureUrlRuleFilter();

        $filter->enabled = true;
        $this->assertTrue($this->invoke($filter, 'isEnabled'));

        $filter->enabled = false;
        $this->assertFalse($this->invoke($filter, 'isEnabled'));

        $filter->enabled = function () {
            return 2 > 1;
        };
        $this->assertTrue($this->invoke($filter, 'isEnabled'));

        $filter->enabled = function () {
            return 2 < 1;
        };
        $this->assertFalse($this->invoke($filter, 'isEnabled'));
    }

    public function testSecureOnlyRoutes()
    {
        $urlManager = $this->mockUrlManager([
            'rules' => [
                '/' => 'site/index',
                'login' => 'auth/login',
            ]
        ]);

        $filter = new SecureUrlRuleFilter();

        $filter->urlManager = $urlManager;
        $filter->secureOnlyRoutes = [
            'auth/login'
        ];

        $this->assertTrue($filter->beforeAction($this->mockAction()));

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
            ]
        ]);

        $filter = new SecureUrlRuleFilter();

        $filter->urlManager = $urlManager;
        $filter->secureExceptRoutes = [
            'site/index'
        ];

        $this->assertTrue($filter->beforeAction($this->mockAction()));

        $this->assertEquals('/index.php/login', $urlManager->createUrl(['auth/login']));
        $this->assertEquals('http://domain.com/index.php', $urlManager->createUrl(['site/index']));
    }
}