<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\https;

/**
 * UrlManager is an enhanced version of [[\yii\web\UrlManager]] with [[SecureConnectionUrlManagerTrait]] applied.
 *
 * @see UrlManager
 * @see SecureConnectionUrlManagerTrait
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class UrlManager extends \yii\web\UrlManager
{
    use SecureConnectionUrlManagerTrait;
}