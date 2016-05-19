<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\https;

use Yii;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;

/**
 * SecureConnectionFilter is an action filter, which performs automatic redirection from 'http' to 'https' protocol
 * depending of which one is required by particular action.
 *
 * This filter can be used at module (application) level or at controller level.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'as https' => [
 *         'class' => 'yii2tech\https\SecureConnectionFilter',
 *         'secureOnly' => [
 *             'site/login',
 *             'site/signup',
 *         ],
 *     ],
 *     // ...
 * ];
 * ```
 *
 * Controller configuration example:
 *
 * ```php
 * use yii\web\Controller;
 * use yii2tech\https\SecureConnectionFilter;
 *
 * class SiteController extends Controller
 * {
 *     public function behaviors()
 *     {
 *         return [
 *             'https' => [
 *                 'class' => SecureConnectionFilter::className(),
 *                 'secureOnly' => [
 *                     'login',
 *                     'signup',
 *                 ],
 *             ],
 *         ];
 *     }
 *
 *     // ...
 * }
 * ```
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class SecureConnectionFilter extends ActionFilter
{
    /**
     * @var boolean|callable whether the filter is enabled or not.
     * You may use this field for quick disabling of the filter, based on debug mode or environment.
     * This value can be a callable, which returns actual boolean result for the check.
     */
    public $enabled = true;
    /**
     * @var array list of action IDs that require secure connection. If this property is not set,
     * then all actions will require secure connection, unless they are listed in [[secureExcept]].
     * If an action ID appears in both [[secureOnly]] and [[secureExcept]], secure connection will NOT be required.
     *
     * Note that if the filter is attached to a module, the action IDs should also include child module IDs (if any)
     * and controller IDs.
     *
     * @see secureExcept
     */
    public $secureOnly;
    /**
     * @var array list of action IDs that should not require secure connection.
     * @see secureOnly
     */
    public $secureExcept = [];
    /**
     * @var array list of request methods, which should allow page redirection in case wrong protocol is used.
     * For all not listed request methods `BadRequestHttpException` will be thrown for secure action, while
     * not secure ones will be allowed to be performed via secured protocol.
     */
    public $readRequestMethods = ['GET', 'OPTIONS'];


    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if ($this->isSecure($action)) {
            // https only :
            if (Yii::$app->getRequest()->getIsSecureConnection()) {
                return true;
            }

            if ($this->isReadRequestMethod()) {
                $this->redirect();
                return false;
            }
            throw new BadRequestHttpException();
        }

        // https not required :
        if (!Yii::$app->getRequest()->getIsSecureConnection()) {
            return true;
        }
        if ($this->isReadRequestMethod()) {
            $this->redirect();
            return false;
        }
        return true; // allow form submission via secure protocol
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
     * Returns a value indicating whether the given action should be run with secure connection.
     * @param Action $action the action being filtered
     * @return boolean whether the action should be run with secure connection.
     */
    protected function isSecure($action)
    {
        $id = $this->getActionId($action);
        return !in_array($id, $this->secureExcept, true) && (empty($this->secureOnly) || in_array($id, $this->secureOnly, true));
    }

    /**
     * @return boolean whether current web request method is considered as 'read' type.
     */
    protected function isReadRequestMethod()
    {
        return in_array(Yii::$app->getRequest()->getMethod(), $this->readRequestMethods, true);
    }

    /**
     * Performs redirection to the protocol, which is opposite to the current one.
     * @return \yii\web\Response response instance.
     */
    protected function redirect()
    {
        $schema = Yii::$app->getRequest()->getIsSecureConnection() ? 'http' : 'https';
        return Yii::$app->getResponse()->redirect(Url::current([], $schema));
    }
}