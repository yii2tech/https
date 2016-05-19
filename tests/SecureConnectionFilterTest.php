<?php

namespace yii2tech\tests\unit\https;

use Yii;
use yii\base\Action;
use yii\web\Controller;
use yii2tech\https\SecureConnectionFilter;

class SecureConnectionFilterTest extends TestCase
{
    /**
     * @return Action test action instance.
     */
    protected function mockAction()
    {
        $controller = new Controller('site', Yii::$app);
        return new Action('test', $controller);
    }

    // Tests :

    public function testIsEnabled()
    {
        $filter = new SecureConnectionFilter();

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

    public function testIsSecure()
    {
        $action = $this->mockAction();

        $filter = new SecureConnectionFilter();
        $filter->secureOnly = ['test'];
        $action->id = 'test';
        $this->assertTrue($this->invoke($filter, 'isSecure', [$action]));
        $action->id = 'tester';
        $this->assertFalse($this->invoke($filter, 'isSecure', [$action]));

        $filter = new SecureConnectionFilter();
        $filter->secureExcept = ['test'];
        $action->id = 'test';
        $this->assertFalse($this->invoke($filter, 'isSecure', [$action]));
        $action->id = 'tester';
        $this->assertTrue($this->invoke($filter, 'isSecure', [$action]));
    }

    /**
     * @depends testIsSecure
     */
    public function testIsSecureWildcard()
    {
        $action = $this->mockAction();

        $filter = new SecureConnectionFilter();
        $filter->secureOnly = ['test/*'];
        $action->id = 'test/foo';
        $this->assertTrue($this->invoke($filter, 'isSecure', [$action]));
        $action->id = 'test';
        $this->assertFalse($this->invoke($filter, 'isSecure', [$action]));
        $action->id = 'test';
        $this->assertFalse($this->invoke($filter, 'isSecure', [$action]));

        $filter = new SecureConnectionFilter();
        $filter->secureExcept = ['test/*'];
        $action->id = 'test/foo';
        $this->assertFalse($this->invoke($filter, 'isSecure', [$action]));
        $action->id = 'foo/test';
        $this->assertTrue($this->invoke($filter, 'isSecure', [$action]));
        $action->id = 'foo/some';
        $this->assertTrue($this->invoke($filter, 'isSecure', [$action]));
    }

    public function testIsReadRequestMethod()
    {
        $filter = new SecureConnectionFilter();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertTrue($this->invoke($filter, 'isReadRequestMethod'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse($this->invoke($filter, 'isReadRequestMethod'));
    }
}