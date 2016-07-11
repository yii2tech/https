<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\https;

use Yii;
use yii\base\ActionFilter;
use yii\base\InvalidConfigException;
use yii\web\UrlManager;
use yii\web\UrlRule;

/**
 * SecureUrlRuleFilter is an action filter, which adjusts [[UrlManager::rules]] to automatically create URLs with
 * correct protocol 'http' or 'https'.
 * After being applied it ensures [[UrlManager::createUrl()]] will automatically create absolute URL with leading protocol
 * in case it miss matches current one.
 *
 * This filter can be used at module (application) level or at controller level.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'as secureUrlRules' => [
 *         'class' => 'yii2tech\https\SecureUrlRuleFilter',
 *         'secureOnlyRoutes' => [
 *             'auth/login',
 *             'site/signup',
 *         ],
 *         'secureExceptRoutes' => [
 *             'site/index',
 *             'help/<action>',
 *         ],
 *     ],
 *     // ...
 * ];
 * ```
 *
 * Note: this filter will take affect only if [[UrlManager::enablePrettyUrl]] is enabled.
 *
 * Attention: once applied this filter changes the state of related [[UrlManager]], this may break some features,
 * like parsing URL.
 *
 * @see UrlManager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class SecureUrlRuleFilter extends ActionFilter
{
    /**
     * @var boolean|callable whether the filter is enabled or not.
     * You may use this field for quick disabling of the filter, based on debug mode or environment.
     * This value can be a callable, which returns actual boolean result for the check.
     */
    public $enabled = true;
    /**
     * @var string|UrlManager URL manager to be processed. This can be either instance of [[UrlManager]] or
     * corresponding application component name.
     */
    public $urlManager = 'urlManager';
    /**
     * @var array list of the URL routes, which should be secure-only ('https' protocol).
     * Routes should be specified in the same way they are used in [[UrlManager::rules]], including placeholders
     * like `<controller>` and `<action>`. Route can be specified as wildcards, e.g. `auth/*`.
     * For example:
     *
     * ```
     * [
     *     'auth/login',
     *     'payment/<action>',
     *     'some-module/<controller>/<action>',
     *     'credit-card/*',
     * ]
     * ```
     *
     * URL rules, which have host specification or 'parse-only' mode will not be affected.
     */
    public $secureOnlyRoutes = [];
    /**
     * @var array list of the URL routes, which should not be secure ('http' protocol).
     * Routes should be specified in the same way they are used in [[UrlManager::rules]], including placeholders
     * like `<controller>` and `<action>`. Route can be specified as wildcards, e.g. `auth/*`.
     * For example:
     *
     * ```
     * [
     *     'site/index',
     *     'help/<action>',
     *     'some-module/<controller>/<action>',
     *     'pages/*',
     * ]
     * ```
     *
     * URL rules, which have host specification or 'parse-only' mode will not be affected.
     */
    public $secureExceptRoutes = [];


    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if (Yii::$app->getRequest()->getIsSecureConnection() && !empty($this->secureExceptRoutes)) {
            $urlManager = $this->getUrlManager();
            $host = str_replace('https://', 'http://', $urlManager->getHostInfo());
            $this->processUrlRules($urlManager->rules, $this->secureExceptRoutes, $host);
            return true;
        }

        if (!empty($this->secureOnlyRoutes)) {
            $urlManager = $this->getUrlManager();
            $host = str_replace('http://', 'https://', $urlManager->getHostInfo());
            $this->processUrlRules($urlManager->rules, $this->secureOnlyRoutes, $host);
        }

        return true;
    }

    /**
     * @return boolean whether filter is enabled or not.
     */
    protected function isEnabled()
    {
        if (is_bool($this->enabled)) {
            return $this->enabled;
        }
        return call_user_func($this->enabled);
    }

    /**
     * Returns [[UrlManager]] corresponding to [[urlManager]] value.
     * @return UrlManager URL manager instance.
     * @throws InvalidConfigException on invalid [[urlManager]].
     */
    protected function getUrlManager()
    {
        if (is_string($this->urlManager)) {
            return Yii::$app->get($this->urlManager);
        } elseif (!is_object($this->urlManager)) {
            throw new InvalidConfigException('"' . get_class($this) . '::urlManager" must be instance of "yii\web\UrlManager" or application component name.');
        }
        return $this->urlManager;
    }

    /**
     * @param UrlRule[] $rules URL rules to be processed.
     * @param array $routes routes to be matched.
     * @param string $host host to be applied.
     */
    private function processUrlRules($rules, $routes, $host)
    {
        $urlRuleTemplateProperty = $this->getUrlRuleTemplateReflection();

        foreach ($rules as $rule) {
            if ($this->isRuleMatch($rule, $routes)) {
                $rule->host = $host;
                $newTemplate = $rule->host . $urlRuleTemplateProperty->getValue($rule);
                $urlRuleTemplateProperty->setValue($rule, $newTemplate);
            }
        }
    }

    /**
     * Returns reflection for the `_template` property of the [[UrlRule]] class.
     * Sets it to be accessible.
     * @return \ReflectionProperty reflection of the property.
     */
    private function getUrlRuleTemplateReflection()
    {
        $urlRuleReflection = new \ReflectionClass('yii\web\UrlRule');
        $urlRuleTemplateProperty = $urlRuleReflection->getProperty('_template');
        $urlRuleTemplateProperty->setAccessible(true);
        return $urlRuleTemplateProperty;
    }

    /**
     * Checks if URL rule matches the condition for processing and the given routes list.
     * @param UrlRule $rule URL rule instance.
     * @param array $routes routes to be checked against.
     * @return boolean whether the URL rule matching.
     */
    private function isRuleMatch($rule, $routes)
    {
        if (!empty($rule->host) || $rule->mode === UrlRule::PARSING_ONLY) {
            return false;
        }

        foreach ($routes as $route) {
            if (fnmatch($route, $rule->route)) {
                return true;
            }
        }

        return false;
    }
}