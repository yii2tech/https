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

/**
 * SecureConnectionFilter
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
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if ($this->isSecure($action)) {
            if (Yii::$app->getRequest()->getIsSecureConnection()) {
                return true;
            }
            $this->redirect();
            return false;
        }

        if (!Yii::$app->getRequest()->getIsSecureConnection()) {
            return true;
        }
        $this->redirect();
        return false;
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
     * Performs redirection to the protocol, which is opposite to the current one.
     * @return \yii\web\Response response instance.
     */
    protected function redirect()
    {
        $schema = Yii::$app->getRequest()->getIsSecureConnection() ? 'http' : 'https';
        return Yii::$app->getResponse()->redirect(Url::current([], $schema));
    }
}