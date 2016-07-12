<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\https;

use Yii;

/**
 * SecureConnectionUrlManagerTrait allows automatic creation of URLs with correct protocol: 'http' or 'https'.
 * This trait should be used with [[\yii\web\UrlManager]] descendant class.
 *
 * For example:
 *
 * ```php
 * namespace app\components\web;
 *
 * use yii2tech\https\SecureConnectionUrlManagerTrait;
 *
 * class MyUrlManager extends \yii\web\UrlManager
 * {
 *     use SecureConnectionUrlManagerTrait;
 * }
 * ```
 *
 * @see \yii\web\UrlManager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
trait SecureConnectionUrlManagerTrait
{
    /**
     * @var boolean|callable whether the automatic creation of secure/un-secure URLs for [[secureOnlyRoutes]]
     * and [[secureExceptRoutes]] is enabled.
     * You may use this field for quick disabling of this functionality, based on debug mode or environment.
     * This value can be a callable, which returns actual boolean result for the check.
     */
    public $enableAutoSecureRoutes = true;
    /**
     * @var array list of the URL routes, which should be secure-only ('https' protocol).
     * Route can be specified as wildcards, e.g. `auth/*`.
     * For example:
     *
     * ```
     * [
     *     'auth/login',
     *     'payment/cart/index',
     *     'credit-card/*',
     * ]
     * ```
     */
    public $secureOnlyRoutes = [];
    /**
     * @var array list of the URL routes, which should not be secure ('http' protocol).
     * Route can be specified as wildcards, e.g. `site/*`.
     * For example:
     *
     * ```
     * [
     *     'site/index',
     *     'help/contact',
     *     'pages/*',
     * ]
     * ```
     */
    public $secureExceptRoutes = [];


    /**
     * Initializes UrlManager.
     * @see \yii\web\UrlManager::init()
     */
    public function init()
    {
        parent::init();

        if (!is_bool($this->enableAutoSecureRoutes)) {
            $this->enableAutoSecureRoutes = call_user_func($this->enableAutoSecureRoutes);
        }
    }

    /**
     * Creates a URL using the given route and query parameters.
     *
     * @see \yii\web\UrlManager::createUrl()
     *
     * @param string|array $params use a string to represent a route (e.g. `site/index`),
     * or an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     * @return string the created URL
     */
    public function createUrl($params)
    {
        /* @var $this \yii\web\UrlManager|SecureConnectionUrlManagerTrait */
        $url = parent::createUrl($params);

        if ($this->enableAutoSecureRoutes && strpos($url, '://') === false) {
            $route = trim($params[0], '/');

            if (Yii::$app->getRequest()->getIsSecureConnection()) {
                foreach ($this->secureExceptRoutes as $routePattern) {
                    if (fnmatch($routePattern, $route)) {
                        return str_replace('https://', 'http://', $this->getHostInfo()) . $url;
                    }
                }
            } else {
                foreach ($this->secureOnlyRoutes as $routePattern) {
                    if (fnmatch($routePattern, $route)) {
                        return str_replace('http://', 'https://', $this->getHostInfo()) . $url;
                    }
                }
            }
        }

        return $url;
    }
}