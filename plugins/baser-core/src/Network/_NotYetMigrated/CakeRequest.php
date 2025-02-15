<?php
// TODO : コード確認要
return;
/**
 * CakeRequest
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Hash', 'Utility');

/**
 * A class that helps wrap Request information and particulars about a single request.
 * Provides methods commonly used to introspect on the request headers and request body.
 *
 * Has both an Array and Object interface. You can access framework parameters using indexes:
 *
 * `$request['controller']` or `$request->controller`.
 *
 * @package       Cake.Network
 */
class CakeRequest implements ArrayAccess
{

    /**
     * Array of parameters parsed from the URL.
     *
     * @var array
     */
    public $params = [
        'plugin' => null,
        'controller' => null,
        'action' => null,
        'named' => [],
        'pass' => [],
    ];

    /**
     * Array of POST data. Will contain form data as well as uploaded files.
     * Inputs prefixed with 'data' will have the data prefix removed. If there is
     * overlap between an input prefixed with data and one without, the 'data' prefixed
     * value will take precedence.
     *
     * @var array
     */
    public $data = [];

    /**
     * Array of querystring arguments
     *
     * @var array
     */
    public $query = [];

    /**
     * The URL string used for the request.
     *
     * @var string
     */
    public $url;

    /**
     * Base URL path.
     *
     * @var string
     */
    public $base = false;

    /**
     * webroot path segment for the request.
     *
     * @var string
     */
    public $webroot = '/';

    /**
     * The full address to the current request
     *
     * @var string
     */
    public $here = null;

    /**
     * The built in detectors used with `is()` can be modified with `addDetector()`.
     *
     * There are several ways to specify a detector, see CakeRequest::addDetector() for the
     * various formats and ways to define detectors.
     *
     * @var array
     */
    protected $_detectors = [
        'get' => ['env' => 'REQUEST_METHOD', 'value' => 'GET'],
        'patch' => ['env' => 'REQUEST_METHOD', 'value' => 'PATCH'],
        'post' => ['env' => 'REQUEST_METHOD', 'value' => 'POST'],
        'put' => ['env' => 'REQUEST_METHOD', 'value' => 'PUT'],
        'delete' => ['env' => 'REQUEST_METHOD', 'value' => 'DELETE'],
        'head' => ['env' => 'REQUEST_METHOD', 'value' => 'HEAD'],
        'options' => ['env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'],
        'ssl' => ['env' => 'HTTPS', 'value' => 1],
        'ajax' => ['env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'],
        'flash' => ['env' => 'HTTP_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/'],
        'mobile' => ['env' => 'HTTP_USER_AGENT', 'options' => [
            'Android', 'AvantGo', 'BB10', 'BlackBerry', 'DoCoMo', 'Fennec', 'iPod', 'iPhone', 'iPad',
            'J2ME', 'MIDP', 'NetFront', 'Nokia', 'Opera Mini', 'Opera Mobi', 'PalmOS', 'PalmSource',
            'portalmmm', 'Plucker', 'ReqwirelessWeb', 'SonyEricsson', 'Symbian', 'UP\\.Browser',
            'webOS', 'Windows CE', 'Windows Phone OS', 'Xiino'
        ]],
        'requested' => ['param' => 'requested', 'value' => 1],
        'json' => ['accept' => ['application/json'], 'param' => 'ext', 'value' => 'json'],
        'xml' => ['accept' => ['application/xml', 'text/xml'], 'param' => 'ext', 'value' => 'xml'],
    ];

    /**
     * Copy of php://input. Since this stream can only be read once in most SAPI's
     * keep a copy of it so users don't need to know about that detail.
     *
     * @var string
     */
    protected $_input = '';

    /**
     * Constructor
     *
     * @param string $url Trimmed URL string to use. Should not contain the application base path.
     * @param bool $parseEnvironment Set to false to not auto parse the environment. ie. GET, POST and FILES.
     */
    public function __construct($url = null, $parseEnvironment = true)
    {
        $this->_base();
        if (empty($url)) {
            $url = $this->_url();
        }
        if ($url[0] === '/') {
            $url = substr($url, 1);
        }
        $this->url = $url;

        if ($parseEnvironment) {
            $this->_processPost();
            $this->_processGet();
            $this->_processFiles();
        }
        $this->here = $this->base . '/' . $this->url;
    }

    /**
     * process the post data and set what is there into the object.
     * processed data is available at `$this->data`
     *
     * Will merge POST vars prefixed with `data`, and ones without
     * into a single array. Variables prefixed with `data` will overwrite those without.
     *
     * If you have mixed POST values be careful not to make any top level keys numeric
     * containing arrays. Hash::merge() is used to merge data, and it has possibly
     * unexpected behavior in this situation.
     *
     * @return void
     */
    protected function _processPost()
    {
        if ($_POST) {
            $this->data = $_POST;
        } elseif (($this->is('put') || $this->is('delete')) &&
            strpos($this->contentType(), 'application/x-www-form-urlencoded') === 0
        ) {
            $data = $this->_readInput();
            parse_str($data, $this->data);
        }
        if (ini_get('magic_quotes_gpc') === '1') {
            $this->data = stripslashes_deep($this->data);
        }

        $override = null;
        if (env('HTTP_X_HTTP_METHOD_OVERRIDE')) {
            $this->data['_method'] = env('HTTP_X_HTTP_METHOD_OVERRIDE');
            $override = $this->data['_method'];
        }

        $isArray = is_array($this->data);
        if ($isArray && isset($this->data['_method'])) {
            if (!empty($_SERVER)) {
                $_SERVER['REQUEST_METHOD'] = $this->data['_method'];
            } else {
                $_ENV['REQUEST_METHOD'] = $this->data['_method'];
            }
            $override = $this->data['_method'];
            unset($this->data['_method']);
        }

        if ($override && !in_array($override, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->data = [];
        }

        if ($isArray && isset($this->data['data'])) {
            $data = $this->data['data'];
            if (count($this->data) <= 1) {
                $this->data = $data;
            } else {
                unset($this->data['data']);
                $this->data = Hash::merge($this->data, $data);
            }
        }
    }

    /**
     * Process the GET parameters and move things into the object.
     *
     * @return void
     */
    protected function _processGet()
    {
        if (ini_get('magic_quotes_gpc') === '1') {
            $query = stripslashes_deep($_GET);
        } else {
            $query = $_GET;
        }

        $unsetUrl = '/' . str_replace(['.', ' '], '_', rawurldecode($this->url));
        unset($query[$unsetUrl]);
        unset($query[$this->base . $unsetUrl]);
        if (strpos($this->url, '?') !== false) {
            [$this->url, $querystr] = explode('?', $this->url);
            parse_str($querystr, $queryArgs);
            $query += $queryArgs;
        }
        if (isset($this->params['url'])) {
            $query = array_merge($this->params['url'], $query);
        }
        $this->query = $query;
    }

    /**
     * Get the request uri. Looks in PATH_INFO first, as this is the exact value we need prepared
     * by PHP. Following that, REQUEST_URI, PHP_SELF, HTTP_X_REWRITE_URL and argv are checked in that order.
     * Each of these server variables have the base path, and query strings stripped off
     *
     * @return string URI The CakePHP request path that is being accessed.
     */
    protected function _url()
    {
        $uri = '';
        if (!empty($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        } elseif (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '://') === false) {
            $uri = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $qPosition = strpos($_SERVER['REQUEST_URI'], '?');
            if ($qPosition !== false && strpos($_SERVER['REQUEST_URI'], '://') > $qPosition) {
                $uri = $_SERVER['REQUEST_URI'];
            } else {
                // CUSTOMIZE MODIFY 2019/09/18 CUiwamoto
                // urlのアクション部分にフルパスが来るとトップページに遷移してしまう為、404となるように修正
                // >>>
                // $uri = substr($_SERVER['REQUEST_URI'], strlen(Configure::read('App.fullBaseUrl')));
                // ---
                if (strpos($_SERVER['REQUEST_URI'], Configure::read('App.fullBaseUrl')) === 0) {
                    $uri = substr($_SERVER['REQUEST_URI'], strlen(Configure::read('App.fullBaseUrl')));
                } else {
                    $uri = $_SERVER['REQUEST_URI'];
                }
                // <<<
            }
        } elseif (isset($_SERVER['PHP_SELF']) && isset($_SERVER['SCRIPT_NAME'])) {
            $uri = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $uri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif ($var = env('argv')) {
            $uri = $var[0];
        }

        // CUSTOMIZE ADD 2013/11/25 ryuring
        // サブフォルダに設置した場合URIを正常に取得できない為調整
        // >>>
        if (Configure::read('App.baseUrl')) {
            $dir = dirname($this->base);
            if (strpos($uri, $dir) === 0) {
                //$uri = substr($uri, strlen($dir));
            }
        }
        // <<<
        // CUSTOMIZE MODIFY 2013/09/30 ryuring
        // サブディレクトリ設置時のスマートURLオフに対応していなかったので調整
        // >>>
        //$base = $this->base;
        // ---
        $base = str_replace('/index.php', '', $this->base);
        // <<<

        if (strlen($base) > 0 && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        if (strpos($uri, '?') !== false) {
            [$uri] = explode('?', $uri, 2);
        }
        if (empty($uri) || $uri === '/' || $uri === '//' || $uri === '/index.php') {
            $uri = '/';
        }
        $endsWithIndex = '/webroot/index.php';
        $endsWithLength = strlen($endsWithIndex);
        if (strlen($uri) >= $endsWithLength &&
            substr($uri, -$endsWithLength) === $endsWithIndex
        ) {
            $uri = '/';
        }
        return $uri;
    }

    /**
     * Returns a base URL and sets the proper webroot
     *
     * If CakePHP is called with index.php in the URL even though
     * URL Rewriting is activated (and thus not needed) it swallows
     * the unnecessary part from $base to prevent issue #3318.
     *
     * @return string Base URL
     */
    protected function _base()
    {
        $dir = $webroot = null;
        $config = Configure::read('App');
        extract($config);

        if (!isset($base)) {
            $base = $this->base;
        }
        if ($base !== false) {
            $this->webroot = $base . '/';
            return $this->base = $base;
        }

        if (empty($baseUrl)) {
            $base = dirname(env('PHP_SELF'));
            // Clean up additional / which cause following code to fail..
            $base = preg_replace('#/+#', '/', $base);

            $indexPos = strpos($base, '/webroot/index.php');
            if ($indexPos !== false) {
                $base = substr($base, 0, $indexPos) . '/webroot';
            }
            if ($webroot === 'webroot' && $webroot === basename($base)) {
                $base = dirname($base);
            }
            if ($dir === 'app' && $dir === basename($base)) {
                $base = dirname($base);
            }

            if ($base === DS || $base === '.') {
                $base = '';
            }
            $base = implode('/', array_map('rawurlencode', explode('/', $base)));
            $this->webroot = $base . '/';

            return $this->base = $base;
        }

        $file = '/' . basename($baseUrl);
        $base = dirname($baseUrl);

        if ($base === DS || $base === '.') {
            $base = '';
        }
        $this->webroot = $base . '/';

        $docRoot = env('DOCUMENT_ROOT');
        $docRootContainsWebroot = strpos($docRoot, $dir . DS . $webroot);

        // CUSTOMIZE MODIFY 2013/11/25 ryuring
        // >>>
        //if (!empty($base) || !$docRootContainsWebroot) {
        // ---
        if ((!empty($base) || !$docRootContainsWebroot)) {
            // <<<
            if (strpos($this->webroot, '/' . $dir . '/') === false) {
                $this->webroot .= $dir . '/';
            }
            if (strpos($this->webroot, '/' . $webroot . '/') === false) {
                $this->webroot .= $webroot . '/';
            }
        }
        return $this->base = $base . $file;
    }

    /**
     * Process $_FILES and move things into the object.
     *
     * @return void
     */
    protected function _processFiles()
    {
        if (isset($_FILES) && is_array($_FILES)) {
            foreach($_FILES as $name => $data) {
                if ($name !== 'data') {
                    $this->params['form'][$name] = $data;
                }
            }
        }

        if (isset($_FILES['data'])) {
            foreach($_FILES['data'] as $key => $data) {
                $this->_processFileData('', $data, $key);
            }
        }
    }

    /**
     * Recursively walks the FILES array restructuring the data
     * into something sane and useable.
     *
     * @param string $path The dot separated path to insert $data into.
     * @param array $data The data to traverse/insert.
     * @param string $field The terminal field name, which is the top level key in $_FILES.
     * @return void
     */
    protected function _processFileData($path, $data, $field)
    {
        foreach($data as $key => $fields) {
            $newPath = $key;
            if (strlen($path) > 0) {
                $newPath = $path . '.' . $key;
            }
            if (is_array($fields)) {
                $this->_processFileData($newPath, $fields, $field);
            } else {
                $newPath .= '.' . $field;
                $this->data = Hash::insert($this->data, $newPath, $fields);
            }
        }
    }

    /**
     * Get the content type used in this request.
     *
     * @return string
     */
    public function contentType()
    {
        $type = env('CONTENT_TYPE');
        if ($type) {
            return $type;
        }
        return env('HTTP_CONTENT_TYPE');
    }

    /**
     * Get the IP the client is using, or says they are using.
     *
     * @param bool $safe Use safe = false when you think the user might manipulate their HTTP_CLIENT_IP
     *   header. Setting $safe = false will also look at HTTP_X_FORWARDED_FOR
     * @return string The client IP.
     */
    public function clientIp($safe = true)
    {
        if (!$safe && env('HTTP_X_FORWARDED_FOR')) {
            $ipaddr = preg_replace('/(?:,.*)/', '', env('HTTP_X_FORWARDED_FOR'));
        } elseif (!$safe && env('HTTP_CLIENT_IP')) {
            $ipaddr = env('HTTP_CLIENT_IP');
        } else {
            $ipaddr = env('REMOTE_ADDR');
        }
        return trim($ipaddr);
    }

    /**
     * Returns the referer that referred this request.
     *
     * @param bool $local Attempt to return a local address. Local addresses do not contain hostnames.
     * @return string The referring address for this request.
     */
    public function referer($local = false)
    {
        $ref = env('HTTP_REFERER');

        $base = Configure::read('App.fullBaseUrl') . $this->webroot;
        if (!empty($ref) && !empty($base)) {
            if ($local && strpos($ref, $base) === 0) {
                $ref = substr($ref, strlen($base));
                if (!strlen($ref) || strpos($ref, '//') === 0) {
                    $ref = '/';
                }
                if ($ref[0] !== '/') {
                    $ref = '/' . $ref;
                }
                return $ref;
            } elseif (!$local) {
                return $ref;
            }
        }
        return '/';
    }

    /**
     * Missing method handler, handles wrapping older style isAjax() type methods
     *
     * @param string $name The method called
     * @param array $params Array of parameters for the method call
     * @return mixed
     * @throws CakeException when an invalid method is called.
     */
    public function __call($name, $params)
    {
        if (strpos($name, 'is') === 0) {
            $type = strtolower(substr($name, 2));
            return $this->is($type);
        }
        throw new CakeException(__d('cake_dev', 'Method %s does not exist', $name));
    }

    /**
     * Magic get method allows access to parsed routing parameters directly on the object.
     *
     * Allows access to `$this->params['controller']` via `$this->controller`
     *
     * @param string $name The property being accessed.
     * @return mixed Either the value of the parameter or null.
     */
    public function __get($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        return null;
    }

    /**
     * Magic isset method allows isset/empty checks
     * on routing parameters.
     *
     * @param string $name The property being accessed.
     * @return bool Existence
     */
    public function __isset($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * Check whether or not a Request is a certain type.
     *
     * Uses the built in detection rules as well as additional rules
     * defined with CakeRequest::addDetector(). Any detector can be called
     * as `is($type)` or `is$Type()`.
     *
     * @param string|string[] $type The type of request you want to check. If an array
     *   this method will return true if the request matches any type.
     * @return bool Whether or not the request is the type you are checking.
     */
    public function is($type)
    {
        if (is_array($type)) {
            foreach($type as $_type) {
                if ($this->is($_type)) {
                    return true;
                }
            }
            return false;
        }
        $type = strtolower($type);
        if (!isset($this->_detectors[$type])) {
            return false;
        }
        $detect = $this->_detectors[$type];
        if (isset($detect['env']) && $this->_environmentDetector($detect)) {
            return true;
        }
        if (isset($detect['header']) && $this->_headerDetector($detect)) {
            return true;
        }
        if (isset($detect['accept']) && $this->_acceptHeaderDetector($detect)) {
            return true;
        }
        if (isset($detect['param']) && $this->_paramDetector($detect)) {
            return true;
        }
        if (isset($detect['callback']) && is_callable($detect['callback'])) {
            return call_user_func($detect['callback'], $this);
        }
        return false;
    }

    /**
     * Detects if a URL extension is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _extensionDetector($detect)
    {
        if (is_string($detect['extension'])) {
            $detect['extension'] = [$detect['extension']];
        }
        if (in_array($this->params['ext'], $detect['extension'])) {
            return true;
        }
        return false;
    }

    /**
     * Detects if a specific accept header is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _acceptHeaderDetector($detect)
    {
        $acceptHeaders = explode(',', (string)env('HTTP_ACCEPT'));
        foreach($detect['accept'] as $header) {
            if (in_array($header, $acceptHeaders)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detects if a specific header is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _headerDetector($detect)
    {
        foreach($detect['header'] as $header => $value) {
            $header = env('HTTP_' . strtoupper($header));
            if (!is_null($header)) {
                if (!is_string($value) && !is_bool($value) && is_callable($value)) {
                    return call_user_func($value, $header);
                }
                return ($header === $value);
            }
        }
        return false;
    }

    /**
     * Detects if a specific request parameter is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _paramDetector($detect)
    {
        $key = $detect['param'];
        if (isset($detect['value'])) {
            $value = $detect['value'];
            return isset($this->params[$key])? $this->params[$key] == $value : false;
        }
        if (isset($detect['options'])) {
            return isset($this->params[$key])? in_array($this->params[$key], $detect['options']) : false;
        }
        return false;
    }

    /**
     * Detects if a specific environment variable is present.
     *
     * @param array $detect Detector options array.
     * @return bool Whether or not the request is the type you are checking.
     */
    protected function _environmentDetector($detect)
    {
        if (isset($detect['env'])) {
            if (isset($detect['value'])) {
                return env($detect['env']) == $detect['value'];
            }
            if (isset($detect['pattern'])) {
                return (bool)preg_match($detect['pattern'], env($detect['env']));
            }
            if (isset($detect['options'])) {
                $pattern = '/' . implode('|', $detect['options']) . '/i';
                return (bool)preg_match($pattern, env($detect['env']));
            }
        }
        return false;
    }

    /**
     * Check that a request matches all the given types.
     *
     * Allows you to test multiple types and union the results.
     * See CakeRequest::is() for how to add additional types and the
     * built-in types.
     *
     * @param array $types The types to check.
     * @return bool Success.
     * @see CakeRequest::is()
     */
    public function isAll(array $types)
    {
        foreach($types as $type) {
            if (!$this->is($type)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add a new detector to the list of detectors that a request can use.
     * There are several different formats and types of detectors that can be set.
     *
     * ### Environment value comparison
     *
     * An environment value comparison, compares a value fetched from `env()` to a known value
     * the environment value is equality checked against the provided value.
     *
     * e.g `addDetector('post', array('env' => 'REQUEST_METHOD', 'value' => 'POST'))`
     *
     * ### Pattern value comparison
     *
     * Pattern value comparison allows you to compare a value fetched from `env()` to a regular expression.
     *
     * e.g `addDetector('iphone', array('env' => 'HTTP_USER_AGENT', 'pattern' => '/iPhone/i'));`
     *
     * ### Option based comparison
     *
     * Option based comparisons use a list of options to create a regular expression. Subsequent calls
     * to add an already defined options detector will merge the options.
     *
     * e.g `addDetector('mobile', array('env' => 'HTTP_USER_AGENT', 'options' => array('Fennec')));`
     *
     * ### Callback detectors
     *
     * Callback detectors allow you to provide a 'callback' type to handle the check. The callback will
     * receive the request object as its only parameter.
     *
     * e.g `addDetector('custom', array('callback' => array('SomeClass', 'somemethod')));`
     *
     * ### Request parameter detectors
     *
     * Allows for custom detectors on the request parameters.
     *
     * e.g `addDetector('requested', array('param' => 'requested', 'value' => 1)`
     *
     * You can also make parameter detectors that accept multiple values
     * using the `options` key. This is useful when you want to check
     * if a request parameter is in a list of options.
     *
     * `addDetector('extension', array('param' => 'ext', 'options' => array('pdf', 'csv'))`
     *
     * @param string $name The name of the detector.
     * @param array $options The options for the detector definition. See above.
     * @return void
     */
    public function addDetector($name, $options)
    {
        $name = strtolower($name);
        if (isset($this->_detectors[$name]) && isset($options['options'])) {
            $options = Hash::merge($this->_detectors[$name], $options);
        }
        $this->_detectors[$name] = $options;
    }

    /**
     * Add parameters to the request's parsed parameter set. This will overwrite any existing parameters.
     * This modifies the parameters available through `$request->params`.
     *
     * @param array $params Array of parameters to merge in
     * @return self
     */
    public function addParams($params)
    {
        $this->params = array_merge($this->params, (array)$params);
        return $this;
    }

    /**
     * Add paths to the requests' paths vars. This will overwrite any existing paths.
     * Provides an easy way to modify, here, webroot and base.
     *
     * @param array $paths Array of paths to merge in
     * @return self
     */
    public function addPaths($paths)
    {
        foreach(['webroot', 'here', 'base'] as $element) {
            if (isset($paths[$element])) {
                $this->{$element} = $paths[$element];
            }
        }
        return $this;
    }

    /**
     * Get the value of the current requests URL. Will include named parameters and querystring arguments.
     *
     * @param bool $base Include the base path, set to false to trim the base path off.
     * @return string the current request URL including query string args.
     */
    public function here($base = true)
    {
        $url = $this->here;
        if (!empty($this->query)) {
            $url .= '?' . http_build_query($this->query, null, '&');
        }
        if (!$base) {
            $url = preg_replace('/^' . preg_quote($this->base, '/') . '/', '', $url, 1);
        }
        return $url;
    }

    /**
     * Read an HTTP header from the Request information.
     *
     * @param string $name Name of the header you want.
     * @return mixed Either false on no header being set or the value of the header.
     */
    public static function header($name)
    {
        $httpName = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$httpName])) {
            return $_SERVER[$httpName];
        }
        // Use the provided value, in some configurations apache will
        // pass Authorization with no prefix and in Titlecase.
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
        return false;
    }

    /**
     * Get the HTTP method used for this request.
     * There are a few ways to specify a method.
     *
     * - If your client supports it you can use native HTTP methods.
     * - You can set the HTTP-X-Method-Override header.
     * - You can submit an input with the name `_method`
     *
     * Any of these 3 approaches can be used to set the HTTP method used
     * by CakePHP internally, and will effect the result of this method.
     *
     * @return string The name of the HTTP method used.
     */
    public function method()
    {
        return env('REQUEST_METHOD');
    }

    /**
     * Get the host that the request was handled on.
     *
     * @param bool $trustProxy Whether or not to trust the proxy host.
     * @return string
     */
    public function host($trustProxy = false)
    {
        if ($trustProxy) {
            return env('HTTP_X_FORWARDED_HOST');
        }
        // CUSTOMIZE MODIFY 2017/4/23 ryuring
        // >>>
        //return env('HTTP_HOST');
        // ---
        return Configure::read('BcEnv.host');
        // <<<
    }

    /**
     * Get the domain name and include $tldLength segments of the tld.
     *
     * @param int $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
     *   While `example.co.uk` contains 2.
     * @return string Domain name without subdomains.
     */
    public function domain($tldLength = 1)
    {
        $segments = explode('.', $this->host());
        $domain = array_slice($segments, -1 * ($tldLength + 1));
        return implode('.', $domain);
    }

    /**
     * Get the subdomains for a host.
     *
     * @param int $tldLength Number of segments your tld contains. For example: `example.com` contains 1 tld.
     *   While `example.co.uk` contains 2.
     * @return array An array of subdomains.
     */
    public function subdomains($tldLength = 1)
    {
        $segments = explode('.', $this->host());
        return array_slice($segments, 0, -1 * ($tldLength + 1));
    }

    /**
     * Find out which content types the client accepts or check if they accept a
     * particular type of content.
     *
     * #### Get all types:
     *
     * `$this->request->accepts();`
     *
     * #### Check for a single type:
     *
     * `$this->request->accepts('application/json');`
     *
     * This method will order the returned content types by the preference values indicated
     * by the client.
     *
     * @param string $type The content type to check for. Leave null to get all types a client accepts.
     * @return mixed Either an array of all the types the client accepts or a boolean if they accept the
     *   provided type.
     */
    public function accepts($type = null)
    {
        $raw = $this->parseAccept();
        $accept = [];
        foreach($raw as $types) {
            $accept = array_merge($accept, $types);
        }
        if ($type === null) {
            return $accept;
        }
        return in_array($type, $accept);
    }

    /**
     * Parse the HTTP_ACCEPT header and return a sorted array with content types
     * as the keys, and pref values as the values.
     *
     * Generally you want to use CakeRequest::accept() to get a simple list
     * of the accepted content types.
     *
     * @return array An array of prefValue => array(content/types)
     */
    public function parseAccept()
    {
        return $this->_parseAcceptWithQualifier($this->header('accept'));
    }

    /**
     * Get the languages accepted by the client, or check if a specific language is accepted.
     *
     * Get the list of accepted languages:
     *
     * ``` CakeRequest::acceptLanguage(); ```
     *
     * Check if a specific language is accepted:
     *
     * ``` CakeRequest::acceptLanguage('es-es'); ```
     *
     * @param string $language The language to test.
     * @return mixed If a $language is provided, a boolean. Otherwise the array of accepted languages.
     */
    public static function acceptLanguage($language = null)
    {
        $raw = static::_parseAcceptWithQualifier(static::header('Accept-Language'));
        $accept = [];
        foreach($raw as $languages) {
            foreach($languages as &$lang) {
                if (strpos($lang, '_')) {
                    $lang = str_replace('_', '-', $lang);
                }
                $lang = strtolower($lang);
            }
            $accept = array_merge($accept, $languages);
        }
        if ($language === null) {
            return $accept;
        }
        return in_array(strtolower($language), $accept);
    }

    /**
     * Parse Accept* headers with qualifier options.
     *
     * Only qualifiers will be extracted, any other accept extensions will be
     * discarded as they are not frequently used.
     *
     * @param string $header Header to parse.
     * @return array
     */
    protected static function _parseAcceptWithQualifier($header)
    {
        $accept = [];
        $header = explode(',', $header);
        foreach(array_filter($header) as $value) {
            $prefValue = '1.0';
            $value = trim($value);

            $semiPos = strpos($value, ';');
            if ($semiPos !== false) {
                $params = explode(';', $value);
                $value = trim($params[0]);
                foreach($params as $param) {
                    $qPos = strpos($param, 'q=');
                    if ($qPos !== false) {
                        $prefValue = substr($param, $qPos + 2);
                    }
                }
            }

            if (!isset($accept[$prefValue])) {
                $accept[$prefValue] = [];
            }
            if ($prefValue) {
                $accept[$prefValue][] = $value;
            }
        }
        krsort($accept);
        return $accept;
    }

    /**
     * Provides a read accessor for `$this->query`. Allows you
     * to use a syntax similar to `CakeSession` for reading URL query data.
     *
     * @param string $name Query string variable name
     * @return mixed The value being read
     */
    public function query($name)
    {
        return Hash::get($this->query, $name);
    }

    /**
     * Provides a read/write accessor for `$this->data`. Allows you
     * to use a syntax similar to `CakeSession` for reading post data.
     *
     * ## Reading values.
     *
     * `$request->data('Post.title');`
     *
     * When reading values you will get `null` for keys/values that do not exist.
     *
     * ## Writing values
     *
     * `$request->data('Post.title', 'New post!');`
     *
     * You can write to any value, even paths/keys that do not exist, and the arrays
     * will be created for you.
     *
     * @param string $name Dot separated name of the value to read/write, one or more args.
     * @return mixed|self Either the value being read, or $this so you can chain consecutive writes.
     */
    public function data($name)
    {
        $args = func_get_args();
        if (count($args) === 2) {
            $this->data = Hash::insert($this->data, $name, $args[1]);
            return $this;
        }
        return Hash::get($this->data, $name);
    }

    /**
     * Safely access the values in $this->params.
     *
     * @param string $name The name of the parameter to get.
     * @return mixed The value of the provided parameter. Will
     *   return false if the parameter doesn't exist or is falsey.
     */
    public function param($name)
    {
        $args = func_get_args();
        if (count($args) === 2) {
            $this->params = Hash::insert($this->params, $name, $args[1]);
            return $this;
        }
        if (!isset($this->params[$name])) {
            return Hash::get($this->params, $name, false);
        }
        return $this->params[$name];
    }

    /**
     * Read data from `php://input`. Useful when interacting with XML or JSON
     * request body content.
     *
     * Getting input with a decoding function:
     *
     * `$this->request->input('json_decode');`
     *
     * Getting input using a decoding function, and additional params:
     *
     * `$this->request->input('Xml::build', array('return' => 'DOMDocument'));`
     *
     * Any additional parameters are applied to the callback in the order they are given.
     *
     * @param string $callback A decoding callback that will convert the string data to another
     *     representation. Leave empty to access the raw input data. You can also
     *     supply additional parameters for the decoding callback using var args, see above.
     * @return mixed The decoded/processed request data.
     */
    public function input($callback = null)
    {
        $input = $this->_readInput();
        $args = func_get_args();
        if (!empty($args)) {
            $callback = array_shift($args);
            array_unshift($args, $input);
            return call_user_func_array($callback, $args);
        }
        return $input;
    }

    /**
     * Modify data originally from `php://input`. Useful for altering json/xml data
     * in middleware or DispatcherFilters before it gets to RequestHandlerComponent
     *
     * @param string $input A string to replace original parsed data from input()
     * @return void
     */
    public function setInput($input)
    {
        $this->_input = $input;
    }

    /**
     * Allow only certain HTTP request methods. If the request method does not match
     * a 405 error will be shown and the required "Allow" response header will be set.
     *
     * Example:
     *
     * $this->request->allowMethod('post', 'delete');
     * or
     * $this->request->allowMethod(array('post', 'delete'));
     *
     * If the request would be GET, response header "Allow: POST, DELETE" will be set
     * and a 405 error will be returned.
     *
     * @param string|array $methods Allowed HTTP request methods.
     * @return bool true
     * @throws MethodNotAllowedException
     */
    public function allowMethod($methods)
    {
        if (!is_array($methods)) {
            $methods = func_get_args();
        }
        foreach($methods as $method) {
            if ($this->is($method)) {
                return true;
            }
        }
        $allowed = strtoupper(implode(', ', $methods));
        $e = new MethodNotAllowedException();
        $e->responseHeader('Allow', $allowed);
        throw $e;
    }

    /**
     * Alias of CakeRequest::allowMethod() for backwards compatibility.
     *
     * @param string|array $methods Allowed HTTP request methods.
     * @return bool true
     * @throws MethodNotAllowedException
     * @see CakeRequest::allowMethod()
     * @deprecated 3.0.0 Since 2.5, use CakeRequest::allowMethod() instead.
     */
    public function onlyAllow($methods)
    {
        if (!is_array($methods)) {
            $methods = func_get_args();
        }
        return $this->allowMethod($methods);
    }

    /**
     * Read data from php://input, mocked in tests.
     *
     * @return string contents of php://input
     */
    protected function _readInput()
    {
        if (empty($this->_input)) {
            $fh = fopen('php://input', 'r');
            $content = stream_get_contents($fh);
            fclose($fh);
            $this->_input = $content;
        }
        return $this->_input;
    }

    /**
     * Array access read implementation
     *
     * @param string $name Name of the key being accessed.
     * @return mixed
     */
    public function offsetGet($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        if ($name === 'url') {
            return $this->query;
        }
        if ($name === 'data') {
            return $this->data;
        }
        return null;
    }

    /**
     * Array access write implementation
     *
     * @param string $name Name of the key being written
     * @param mixed $value The value being written.
     * @return void
     */
    public function offsetSet($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Array access isset() implementation
     *
     * @param string $name thing to check.
     * @return bool
     */
    public function offsetExists($name)
    {
        if ($name === 'url' || $name === 'data') {
            return true;
        }
        return isset($this->params[$name]);
    }

    /**
     * Array access unset() implementation
     *
     * @param string $name Name to unset.
     * @return void
     */
    public function offsetUnset($name)
    {
        unset($this->params[$name]);
    }

// CUSTOMIZE ADD XXXX/XX/XX nakae
// >>>
    /**
     * 現在のURLを正規化して取得する
     *
     * $this->request->here は、ビューキャッシュの命名規則に影響する為、
     * CacheHelper 等で、このメソッドを利用する事で、同一ページによる複数キャッシュの生成を防ぐ
     *
     * （例）
     * /news/ → /news/index
     * /company/ → /company/index
     *
     * @return string
     */
    public function normalizedHere()
    {
        $here = $this->here;
        if (!BcUtil::isAdminSystem() && $this->params['controller'] == 'pages') {
            if (!empty($this->params['pass']) && $this->params['pass'][count($this->params['pass']) - 1] == 'index' && !preg_match('/\/index$/', $here)) {
                if (preg_match('/\/$/', $here)) {
                    $here .= 'index';
                } else {
                    $here .= '/index';
                }
            }
            $here = preg_replace('/\.html$/', '', $here);
        } else {
            if ($this->action == 'index') {
                [$here,] = explode('?', $here);
                if (!empty($this->params['pass'])) {
                    foreach($this->params['pass'] as $pass) {
                        $here = preg_replace('/\/' . $pass . '$/', '', $here);
                    }
                }
                if (!preg_match('/\/index$/', $here)) {
                    if (preg_match('/\/$/', $here)) {
                        $here .= 'index';
                    } else {
                        $here .= '/index';
                    }
                }
            }
        }
        return $here;
    }
// <<<
}
