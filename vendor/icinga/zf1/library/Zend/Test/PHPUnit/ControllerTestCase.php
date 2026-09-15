<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Test
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/** @see Zend_Controller_Front */

use PHPUnit\Framework\TestCase;

require_once 'Zend/Controller/Front.php';

/** @see Zend_Controller_Action_HelperBroker */
require_once 'Zend/Controller/Action/HelperBroker.php';

/** @see Zend_Layout */
require_once 'Zend/Layout.php';

/** @see Zend_Session */
require_once 'Zend/Session.php';

/** @see Zend_Registry */
require_once 'Zend/Registry.php';

/**
 * Functional testing scaffold for MVC applications
 *
 * @uses       PHPUnit\Framework\TestCase
 * @category   Zend
 * @package    Zend_Test
 * @subpackage PHPUnit
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Test_PHPUnit_ControllerTestCase extends TestCase
{
    /**
     * @var mixed Bootstrap file path or callback
     */
    public $bootstrap;

    /**
     * @var Zend_Controller_Front
     */
    protected $_frontController;

    /**
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request;

    /**
     * @var Zend_Controller_Response_Abstract
     */
    protected $_response;

    /**
     * XPath namespaces
     * @var array
     */
    protected $_xpathNamespaces = [];

    /**
     * Overloading: prevent overloading to special properties
     *
     * @param  string $name
     * @param  mixed  $value
     * @throws Zend_Exception
     */
    public function __set($name, $value)
    {
        if (in_array($name, ['request', 'response', 'frontController'])) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception(sprintf('Setting %s object manually is not allowed', $name));
        }
        $this->$name = $value;
    }

    /**
     * Overloading for common properties
     *
     * Provides overloading for request, response, and frontController objects.
     *
     * @param  mixed $name
     * @return null|Zend_Controller_Front|Zend_Controller_Request_HttpTestCase|Zend_Controller_Response_HttpTestCase
     */
    public function __get($name)
    {
        switch ($name) {
            case 'request':
                return $this->getRequest();
            case 'response':
                return $this->getResponse();
            case 'frontController':
                return $this->getFrontController();
        }

        return null;
    }

    /**
     * Set up MVC app
     *
     * Calls {@link bootstrap()} by default
     */
    protected function setUp(): void
    {
        $this->bootstrap();
    }

    /**
     * Bootstrap the front controller
     *
     * Resets the front controller, and then bootstraps it.
     *
     * If {@link $bootstrap} is a callback, executes it; if it is a file, it include's
     * it. When done, sets the test case request and response objects into the
     * front controller.
     */
    final public function bootstrap(): void
    {
        $this->reset();
        if (null !== $this->bootstrap) {
            if ($this->bootstrap instanceof Zend_Application) {
                $this->bootstrap->bootstrap();
                $this->_frontController = $this->bootstrap->getBootstrap()->getResource('frontcontroller');
            } elseif (is_callable($this->bootstrap)) {
                call_user_func($this->bootstrap);
            } elseif (is_string($this->bootstrap)) {
                require_once 'Zend/Loader.php';
                if (Zend_Loader::isReadable($this->bootstrap)) {
                    include $this->bootstrap;
                }
            }
        }
        $this->frontController
             ->setRequest($this->getRequest())
             ->setResponse($this->getResponse());
    }

    /**
     * Dispatch the MVC
     *
     * If a URL is provided, sets it as the request URI in the request object.
     * Then sets test case request and response objects in front controller,
     * disables throwing exceptions, and disables returning the response.
     * Finally, dispatches the front controller.
     *
     * @param string|null $url
     */
    public function dispatch($url = null)
    {
        // redirector should not exit
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $redirector->setExit(false);

        // json helper should not exit
        $json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
        $json->suppressExit = true;

        $request    = $this->getRequest();
        if (null !== $url) {
            $request->setRequestUri($url);
        }
        $request->setPathInfo(null);

        $controller = $this->getFrontController();
        $this->frontController
             ->setRequest($request)
             ->setResponse($this->getResponse())
             ->throwExceptions(false)
             ->returnResponse(false);

        if ($this->bootstrap instanceof Zend_Application) {
            $this->bootstrap->run();
        } else {
            $this->frontController->dispatch();
        }
    }

    /**
     * Reset MVC state
     *
     * Creates new request/response objects, resets the front controller
     * instance, and resets the action helper broker.
     *
     * @todo   Need to update Zend_Layout to add a resetInstance() method
     */
    public function reset()
    {
        $_SESSION = [];
        $_GET     = [];
        $_POST    = [];
        $_COOKIE  = [];
        $this->resetRequest();
        $this->resetResponse();
        Zend_Layout::resetMvcInstance();
        Zend_Controller_Action_HelperBroker::resetHelpers();
        $this->frontController->resetInstance();
        Zend_Session::$_unitTestEnabled = true;
    }

    /**
     * Rest all view placeholders
     */
    protected function _resetPlaceholders()
    {
        $registry = Zend_Registry::getInstance();
        $remove   = [];
        foreach ($registry as $key => $value) {
            if (strstr($key, '_View_')) {
                $remove[] = $key;
            }
        }

        foreach ($remove as $key) {
            unset($registry[$key]);
        }
    }

    /**
     * Reset the request object
     *
     * Useful for test cases that need to test multiple trips to the server.
     *
     * @return Zend_Test_PHPUnit_ControllerTestCase
     */
    public function resetRequest()
    {
        if ($this->_request instanceof Zend_Controller_Request_HttpTestCase) {
            $this->_request->clearQuery()
                           ->clearPost();
        }
        $this->_request = null;
        return $this;
    }

    /**
     * Reset the response object
     *
     * Useful for test cases that need to test multiple trips to the server.
     *
     * @return Zend_Test_PHPUnit_ControllerTestCase
     */
    public function resetResponse()
    {
        $this->_response = null;
        $this->_resetPlaceholders();
        return $this;
    }

    /**
     * Assert that a CSS selector matches at least one node.
     *
     * @param string $selector
     * @param string|null $content
     * @param string $message
     */
    public function assertQuery($selector, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        if ($nodes->length === 0) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" matched any nodes', $selector));
        }
    }

    /**
     * Assert that a CSS selector does not match any nodes.
     *
     * @param string $selector
     * @param string|null $content
     * @param string $message
     */
    public function assertNotQuery($selector, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        if ($nodes->length !== 0) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" matched no nodes', $selector));
        }
    }

    /**
     * Assert that content of a CSS selector contains a string.
     *
     * @param string $selector
     * @param mixed $match
     * @param string|null $content
     * @param string $message
     */
    public function assertQueryContentContains($selector, $match, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        $needle = (string) $match;
        foreach ($nodes as $node) {
            if (strpos($node->textContent, $needle) !== false) {
                return;
            }
        }
        $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" content contains "%s"', $selector, $needle));
    }

    /**
     * Assert that content of a CSS selector does not contain a string.
     *
     * @param string $selector
     * @param mixed $match
     * @param string|null $content
     * @param string $message
     */
    public function assertNotQueryContentContains($selector, $match, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        $needle = (string) $match;
        foreach ($nodes as $node) {
            if (strpos($node->textContent, $needle) !== false) {
                $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" content does not contain "%s"', $selector, $needle));
            }
        }
    }

    /**
     * Assert that content of a CSS selector matches a regex.
     *
     * @param string $selector
     * @param string $pattern
     * @param string|null $content
     * @param string $message
     */
    public function assertQueryContentRegex($selector, $pattern, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        foreach ($nodes as $node) {
            if (preg_match($pattern, $node->textContent)) {
                return;
            }
        }
        $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" content matches "%s"', $selector, $pattern));
    }

    /**
     * Assert that content of a CSS selector does not match a regex.
     *
     * @param string $selector
     * @param string $pattern
     * @param string|null $content
     * @param string $message
     */
    public function assertNotQueryContentRegex($selector, $pattern, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        foreach ($nodes as $node) {
            if (preg_match($pattern, $node->textContent)) {
                $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" content does not match "%s"', $selector, $pattern));
            }
        }
    }

    /**
     * Assert that a CSS selector matches at least a minimum number of nodes.
     *
     * @param string $selector
     * @param int $min
     * @param string|null $content
     * @param string $message
     */
    public function assertQueryCountMin($selector, $min, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        if ($nodes->length < $min) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" matched at least %d nodes', $selector, $min));
        }
    }

    /**
     * Assert that a CSS selector matches at most a maximum number of nodes.
     *
     * @param string $selector
     * @param int $max
     * @param string|null $content
     * @param string $message
     */
    public function assertQueryCountMax($selector, $max, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        if ($nodes->length > $max) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" matched at most %d nodes', $selector, $max));
        }
    }

    /**
     * Assert that a CSS selector matches exactly a number of nodes.
     *
     * @param string $selector
     * @param int $count
     * @param string|null $content
     * @param string $message
     */
    public function assertQueryCount($selector, $count, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryCss($selector, $content);
        if ($nodes->length !== $count) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that selector "%s" matched %d nodes', $selector, $count));
        }
    }

    /**
     * Assert that an XPath query matches at least one node.
     *
     * @param string $xpath
     * @param string|null $content
     * @param string $message
     */
    public function assertXpath($xpath, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        if ($nodes->length === 0) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" matched any nodes', $xpath));
        }
    }

    /**
     * Assert that an XPath query matches no nodes.
     *
     * @param string $xpath
     * @param string|null $content
     * @param string $message
     */
    public function assertNotXpath($xpath, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        if ($nodes->length !== 0) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" matched no nodes', $xpath));
        }
    }

    /**
     * Assert that XPath content contains a string.
     *
     * @param string $xpath
     * @param mixed $match
     * @param string|null $content
     * @param string $message
     */
    public function assertXpathContentContains($xpath, $match, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        $needle = (string) $match;
        foreach ($nodes as $node) {
            if (strpos($node->textContent, $needle) !== false) {
                return;
            }
        }
        $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" content contains "%s"', $xpath, $needle));
    }

    /**
     * Assert that XPath content does not contain a string.
     *
     * @param string $xpath
     * @param mixed $match
     * @param string|null $content
     * @param string $message
     */
    public function assertNotXpathContentContains($xpath, $match, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        $needle = (string) $match;
        foreach ($nodes as $node) {
            if (strpos($node->textContent, $needle) !== false) {
                $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" content does not contain "%s"', $xpath, $needle));
            }
        }
    }

    /**
     * Assert that XPath content matches a regex.
     *
     * @param string $xpath
     * @param string $pattern
     * @param string|null $content
     * @param string $message
     */
    public function assertXpathContentRegex($xpath, $pattern, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        foreach ($nodes as $node) {
            if (preg_match($pattern, $node->textContent)) {
                return;
            }
        }
        $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" content matches "%s"', $xpath, $pattern));
    }

    /**
     * Assert that XPath content does not match a regex.
     *
     * @param string $xpath
     * @param string $pattern
     * @param string|null $content
     * @param string $message
     */
    public function assertNotXpathContentRegex($xpath, $pattern, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        foreach ($nodes as $node) {
            if (preg_match($pattern, $node->textContent)) {
                $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" content does not match "%s"', $xpath, $pattern));
            }
        }
    }

    /**
     * Assert that an XPath query matches at least a minimum number of nodes.
     *
     * @param string $xpath
     * @param int $min
     * @param string|null $content
     * @param string $message
     */
    public function assertXpathCountMin($xpath, $min, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        if ($nodes->length < $min) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" matched at least %d nodes', $xpath, $min));
        }
    }

    /**
     * Assert that an XPath query matches at most a maximum number of nodes.
     *
     * @param string $xpath
     * @param int $max
     * @param string|null $content
     * @param string $message
     */
    public function assertXpathCountMax($xpath, $max, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        if ($nodes->length > $max) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" matched at most %d nodes', $xpath, $max));
        }
    }

    /**
     * Assert that an XPath query matches exactly a number of nodes.
     *
     * @param string $xpath
     * @param int $count
     * @param string|null $content
     * @param string $message
     */
    public function assertXpathCount($xpath, $count, $content = null, $message = '')
    {
        $this->_incrementAssertionCount();
        $nodes = $this->_queryXpath($xpath, $content);
        if ($nodes->length !== $count) {
            $this->_failDomAssertion($message ?: sprintf('Failed asserting that XPath "%s" matched %d nodes', $xpath, $count));
        }
    }

    /**
     * Assert that response is a redirect
     *
     * @param string $message
     */
    public function assertRedirect($message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/Redirect41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_Redirect41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert that response is NOT a redirect
     *
     * @param string $message
     */
    public function assertNotRedirect($message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/Redirect41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_Redirect41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert that response redirects to given URL
     *
     * @param string $url
     * @param string $message
     */
    public function assertRedirectTo($url, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/Redirect41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_Redirect41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $url)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert that response does not redirect to given URL
     *
     * @param string $url
     * @param string $message
     */
    public function assertNotRedirectTo($url, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/Redirect41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_Redirect41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $url)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert that redirect location matches pattern
     *
     * @param string $pattern
     * @param string $message
     */
    public function assertRedirectRegex($pattern, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/Redirect41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_Redirect41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $pattern)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert that redirect location does not match pattern
     *
     * @param string $pattern
     * @param string $message
     */
    public function assertNotRedirectRegex($pattern, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/Redirect41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_Redirect41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $pattern)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert response code
     *
     * @param int $code
     * @param string $message
     */
    public function assertResponseCode($code, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/ResponseHeader41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_ResponseHeader41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $code)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert response code
     *
     * @param int $code
     * @param string $message
     */
    public function assertNotResponseCode($code, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/ResponseHeader41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_ResponseHeader41();
        $constraint->setNegate(true);
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $code)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert response header exists
     *
     * @param string $header
     * @param string $message
     */
    public function assertHeader($header, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/ResponseHeader41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_ResponseHeader41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $header)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert response header does not exist
     *
     * @param string $header
     * @param string $message
     */
    public function assertNotHeader($header, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/ResponseHeader41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_ResponseHeader41();
        $constraint->setNegate(true);
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $header)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert response header exists and contains the given string
     *
     * @param string $header
     * @param string $match
     * @param string $message
     */
    public function assertHeaderContains($header, $match, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/ResponseHeader41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_ResponseHeader41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $header, $match)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert response header does not exist and/or does not contain the given string
     *
     * @param string $header
     * @param string $match
     * @param string $message
     */
    public function assertNotHeaderContains($header, $match, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/ResponseHeader41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_ResponseHeader41();
        $constraint->setNegate(true);
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $header, $match)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert response header exists and matches the given pattern
     *
     * @param string $header
     * @param string $pattern
     * @param string $message
     */
    public function assertHeaderRegex($header, $pattern, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/ResponseHeader41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_ResponseHeader41();
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $header, $pattern)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert response header does not exist and/or does not match the given regex
     *
     * @param string $header
     * @param string $pattern
     * @param string $message
     */
    public function assertNotHeaderRegex($header, $pattern, $message = '')
    {
        $this->_incrementAssertionCount();
        require_once 'Zend/Test/PHPUnit/Constraint/ResponseHeader41.php';
        $constraint = new Zend_Test_PHPUnit_Constraint_ResponseHeader41();
        $constraint->setNegate(true);
        $response   = $this->response;
        if (!$constraint->evaluate($response, __FUNCTION__, false, $header, $pattern)) {
            $constraint->fail($response, $message);
        }
    }

    /**
     * Assert that the last handled request used the given module
     *
     * @param string $module
     * @param string $message
     */
    public function assertModule($module, $message = '')
    {
        $this->_incrementAssertionCount();
        if ($module != $this->request->getModuleName()) {
            $msg = sprintf(
                'Failed asserting last module used <"%s"> was "%s"',
                $this->request->getModuleName(),
                $module
            );
            if (!empty($message)) {
                $msg = $message . "\n" . $msg;
            }
            $this->fail($msg);
        }
    }

    /**
     * Assert that the last handled request did NOT use the given module
     *
     * @param string $module
     * @param string $message
     */
    public function assertNotModule($module, $message = '')
    {
        $this->_incrementAssertionCount();
        if ($module == $this->request->getModuleName()) {
            $msg = sprintf('Failed asserting last module used was NOT "%s"', $module);
            if (!empty($message)) {
                $msg = $message . "\n" . $msg;
            }
            $this->fail($msg);
        }
    }

    /**
     * Assert that the last handled request used the given controller
     *
     * @param string $controller
     * @param string $message
     */
    public function assertController($controller, $message = '')
    {
        $this->_incrementAssertionCount();
        if ($controller != $this->request->getControllerName()) {
            $msg = sprintf(
                'Failed asserting last controller used <"%s"> was "%s"',
                $this->request->getControllerName(),
                $controller
            );
            if (!empty($message)) {
                $msg = $message . "\n" . $msg;
            }
            $this->fail($msg);
        }
    }

    /**
     * Assert that the last handled request did NOT use the given controller
     *
     * @param  string $controller
     * @param  string $message
     */
    public function assertNotController($controller, $message = '')
    {
        $this->_incrementAssertionCount();
        if ($controller == $this->request->getControllerName()) {
            $msg = sprintf(
                'Failed asserting last controller used <"%s"> was NOT "%s"',
                $this->request->getControllerName(),
                $controller
            );
            if (!empty($message)) {
                $msg = $message . "\n" . $msg;
            }
            $this->fail($msg);
        }
    }

    /**
     * Assert that the last handled request used the given action
     *
     * @param string $action
     * @param string $message
     */
    public function assertAction($action, $message = '')
    {
        $this->_incrementAssertionCount();
        if ($action != $this->request->getActionName()) {
            $msg = sprintf('Failed asserting last action used <"%s"> was "%s"', $this->request->getActionName(), $action);
            if (!empty($message)) {
                $msg = $message . "\n" . $msg;
            }
            $this->fail($msg);
        }
    }

    /**
     * Assert that the last handled request did NOT use the given action
     *
     * @param string $action
     * @param string $message
     */
    public function assertNotAction($action, $message = '')
    {
        $this->_incrementAssertionCount();
        if ($action == $this->request->getActionName()) {
            $msg = sprintf('Failed asserting last action used <"%s"> was NOT "%s"', $this->request->getActionName(), $action);
            if (!empty($message)) {
                $msg = $message . "\n" . $msg;
            }
            $this->fail($msg);
        }
    }

    /**
     * Assert that the specified route was used
     *
     * @param string $route
     * @param string $message
     */
    public function assertRoute($route, $message = '')
    {
        $this->_incrementAssertionCount();
        $router = $this->frontController->getRouter();
        if ($route != $router->getCurrentRouteName()) {
            $msg = sprintf(
                'Failed asserting matched route was "%s", actual route is %s',
                $route,
                $router->getCurrentRouteName()
            );
            if (!empty($message)) {
                $msg = $message . "\n" . $msg;
            }
            $this->fail($msg);
        }
    }

    /**
     * Assert that the route matched is NOT as specified
     *
     * @param string $route
     * @param string $message
     */
    public function assertNotRoute($route, $message = '')
    {
        $this->_incrementAssertionCount();
        $router = $this->frontController->getRouter();
        if ($route == $router->getCurrentRouteName()) {
            $msg = sprintf('Failed asserting route matched was NOT "%s"', $route);
            if (!empty($message)) {
                $msg = $message . "\n" . $msg;
            }
            $this->fail($msg);
        }
    }

    /**
     * Retrieve front controller instance
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
        if (null === $this->_frontController) {
            $this->_frontController = Zend_Controller_Front::getInstance();
        }
        return $this->_frontController;
    }

    /**
     * Retrieve test case request object
     *
     * @return Zend_Controller_Request_HttpTestCase
     */
    public function getRequest()
    {
        if (null === $this->_request) {
            require_once 'Zend/Controller/Request/HttpTestCase.php';
            $this->_request = new Zend_Controller_Request_HttpTestCase;
        }
        return $this->_request;
    }

    /**
     * Retrieve test case response object
     *
     * @return Zend_Controller_Response_HttpTestCase
     */
    public function getResponse()
    {
        if (null === $this->_response) {
            require_once 'Zend/Controller/Response/HttpTestCase.php';
            $this->_response = new Zend_Controller_Response_HttpTestCase;
        }
        return $this->_response;
    }

    /**
     * URL Helper
     *
     * @param  array  $urlOptions
     * @param  string $name
     * @param  bool   $reset
     * @param  bool   $encode
     * @throws Exception
     * @throws Zend_Controller_Router_Exception
     * @return string
     */
    public function url($urlOptions = [], $name = null, $reset = false, $encode = true)
    {
        $frontController = $this->getFrontController();
        $router = $frontController->getRouter();
        if (!$router instanceof Zend_Controller_Router_Rewrite) {
            throw new Exception('This url helper utility function only works when the router is of type Zend_Controller_Router_Rewrite');
        }
        if (count($router->getRoutes()) === 0) {
            $router->addDefaultRoutes();
        }
        return $router->assemble($urlOptions, $name, $reset, $encode);
    }

    /**
     * Urlize options
     *
     * @param  array $urlOptions
     * @param  bool  $actionControllerModuleOnly
     * @return array
     */
    public function urlizeOptions($urlOptions, $actionControllerModuleOnly = true)
    {
        $ccToDash = new Zend_Filter_Word_CamelCaseToDash();
        foreach ($urlOptions as $n => $v) {
            if (in_array($n, ['action', 'controller', 'module'])) {
                $urlOptions[$n] = $ccToDash->filter($v);
            }
        }
        return $urlOptions;
    }

    /**
     * Increment assertion count
     */
    protected function _incrementAssertionCount()
    {
        if (method_exists($this, 'addToAssertionCount')) {
            $this->addToAssertionCount(1);
        }

        $stack = debug_backtrace();
        foreach ($stack as $step) {
            if (isset($step['object'])
                && $step['object'] instanceof \PHPUnit\Framework\TestCase
                && $step['object'] !== $this
            ) {
                $step['object']->addToAssertionCount(1);
                break;
            }
        }
    }

    /**
     * Return number of assertions performed on this test case.
     *
     * @return int
     */
    public function getNumAssertions()
    {
        if (method_exists($this, 'numberOfAssertionsPerformed')) {
            return $this->numberOfAssertionsPerformed();
        }

        return 0;
    }

    /**
     * Fail with a Zend_Test constraint exception.
     *
     * @param string $message
     * @throws Zend_Test_PHPUnit_Constraint_Exception
     */
    protected function _failDomAssertion($message)
    {
        require_once 'Zend/Test/PHPUnit/Constraint/Exception.php';
        throw new Zend_Test_PHPUnit_Constraint_Exception($message);
    }

    /**
     * Query DOM using a CSS selector (limited support).
     *
     * @param string $selector
     * @param string|null $content
     * @return DOMNodeList
     */
    protected function _queryCss($selector, $content = null)
    {
        $xpath = $this->_cssToXpath($selector);
        return $this->_queryXpath($xpath, $content);
    }

    /**
     * Query DOM using XPath.
     *
     * @param string $xpath
     * @param string|null $content
     * @return DOMNodeList
     */
    protected function _queryXpath($xpath, $content = null)
    {
        $xpath = $this->_normalizeXpath($xpath);
        $doc = $this->_getDomDocument($content);
        $xpathObj = new DOMXPath($doc);
        return $xpathObj->query($xpath);
    }

    /**
     * Convert a minimal subset of CSS selectors to XPath.
     *
     * Supports tag, #id, and .class with descendant separators.
     *
     * @param string $selector
     * @return string
     */
    protected function _cssToXpath($selector)
    {
        $selector = trim($selector);
        if ($selector === '') {
            return '//*';
        }

        $parts = preg_split('/\s+/', $selector);
        $xpath = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $xpath .= '//' . $this->_cssTokenToXpath($part);
        }
        return $xpath;
    }

    /**
     * Convert a single CSS token to XPath.
     *
     * @param string $token
     * @return string
     */
    protected function _cssTokenToXpath($token)
    {
        $tag = '*';
        $id = null;
        $classes = [];

        if (preg_match('/^([a-zA-Z0-9_-]*)?(?:#([a-zA-Z0-9_-]+))?(?:\.([a-zA-Z0-9_.-]+))?$/', $token, $matches)) {
            if ($matches[1] !== '') {
                $tag = $matches[1];
            }
            if (!empty($matches[2])) {
                $id = $matches[2];
            }
            if (!empty($matches[3])) {
                $classes = array_filter(explode('.', $matches[3]));
            }
        } else {
            return $token;
        }

        $predicates = [];
        if ($id !== null) {
            $predicates[] = "@id='{$id}'";
        }
        foreach ($classes as $class) {
            $predicates[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
        }

        if ($predicates) {
            $tag .= '[' . implode(' and ', $predicates) . ']';
        }

        return $tag;
    }

    /**
     * Build a DOMDocument from response content.
     *
     * @param string|null $content
     * @return DOMDocument
     */
    protected function _getDomDocument($content = null)
    {
        if ($content === null) {
            $content = $this->getResponse()->getBody();
        }

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . (string) $content);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $doc;
    }

    /**
     * Normalize XPath expressions for class matching.
     *
     * @param string $xpath
     * @return string
     */
    protected function _normalizeXpath($xpath)
    {
        return preg_replace(
            "/contains\\(@class,\\s*'([^']+)'\\)/",
            "contains(concat(' ', normalize-space(@class), ' '), '$1')",
            $xpath
        );
    }
}
