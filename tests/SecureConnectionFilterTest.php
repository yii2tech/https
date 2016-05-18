<?php

namespace yii2tech\tests\unit\https;

use yii2tech\https\SecureConnectionFilter;

class SecureConnectionFilterTest extends TestCase
{
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
}