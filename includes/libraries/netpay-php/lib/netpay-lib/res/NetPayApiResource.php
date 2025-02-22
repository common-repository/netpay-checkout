<?php

define('NETPAY_PHP_LIB_VERSION', '2.16.0');
define('NETPAY_API_URL', 'https://api.netpay.co/');
define('NETPAY_VAULT_URL', 'https://vault.netpay.co/');

#[\AllowDynamicProperties]
class NetPayApiResource extends NetPayObject
{
    // Request methods
    const REQUEST_GET = 'GET';
    const REQUEST_POST = 'POST';
    const REQUEST_DELETE = 'DELETE';
    const REQUEST_PATCH = 'PATCH';

    // Timeout settings
    private $NETPAY_CONNECTTIMEOUT = 30;
    private $NETPAY_TIMEOUT = 60;

    protected static $instances = [];

    private static $classesToUsePublicKey = [
        NetPayToken::class,
        NetPaySource::class
    ];

    /**
     * Returns an instance of the class given in $class or raise an error.
     *
     * @param  string $class
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @throws Exception
     *
     * @return NetPayResource
     */
    protected static function getInstance($publickey = null, $secretkey = null)
    {
        $resource = new static($publickey, $secretkey); // @phpstan-ignore-line
        $className = get_class($resource);

        if (!isset(self::$instances[$className])) {
            static::$instances[$className] = $resource;
        }

        return static::$instances[$className];
    }

    /**
     * Retrieves the resource.
     *
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @throws Exception|NetPayException
     *
     * @return NetPayAccount|NetPayBalance|NetPayCharge|NetPayCustomer|NetPayToken|NetPayTransaction|NetPayTransfer
     */
    protected static function g_retrieve($url, $publickey = null, $secretkey = null)
    {
        /*$resource = self::getInstance($publickey, $secretkey);
        $result = $resource->execute($url, self::REQUEST_GET, $resource->getResourceKey());
        $resource->refresh($result);

        return $resource;*/
    }

    /**
     * Creates the resource with given parameters in an associative array.
     *
     * @param  string $url
     * @param  array  $params
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @throws Exception|NetPayException
     *
     * @return NetPayAccount|NetPayBalance|NetPayCharge|NetPayCustomer|NetPayToken|NetPayTransaction|NetPayTransfer
     */
    protected static function g_create($url, $params, $publickey = null, $secretkey = null)
    {
        $resource = self::getInstance($publickey, $secretkey);
        $result = $resource->execute($url, self::REQUEST_POST, $resource->getResourceKey(), $params);
        $resource->refresh($result);

        return $resource;
    }

    /**
     * Updates the resource with the given parameters in an associative array.
     *
     * @param  string $url
     * @param  array  $params
     *
     * @throws Exception|NetPayException
     */
    protected static function g_update($url, $params = null)
    {
        $resource = self::getInstance();
        $result = $resource->execute($url, self::REQUEST_PATCH, $resource->getResourceKey(), $params);
        $resource->refresh($result);
    }

    /**
     * Set the resource to expire.
     *
     * @param  string $url
     *
     * @throws Exception|NetPayException
     */
    protected static function g_expire($url)
    {
        $resource = self::getInstance();
        $result = $resource->execute($url, self::REQUEST_POST, $resource->getResourceKey());
        $resource->refresh($result, true);
    }

    /**
     * Destroys the resource.
     *
     * @param  string $url
     *
     * @throws Exception|NetPayException
     */
    protected static function g_destroy($url)
    {
        $resource = self::getInstance();
        $result = $resource->execute($url, self::REQUEST_DELETE, $resource->getResourceKey());
        $resource->refresh($result, true);
    }

    /**
     * Revokes the resource.
     *
     * @param  string $url
     *
     * @throws Exception|NetPayException
     */
    protected static function g_revoke($url)
    {
        $resource = self::getInstance();
        $result = $resource->execute($url, self::REQUEST_POST, $resource->getResourceKey());
        $resource->refresh($result, true);
    }

    /**
     * Reloads the resource with latest data.
     *
     * @param  string $url
     *
     * @throws Exception|NetPayException
     */
    protected static function g_reload($url)
    {
        $resource = self::getInstance();
        $result = $resource->execute($url, self::REQUEST_GET, $resource->getResourceKey());
        $resource->refresh($result);
    }

    /**
     * Makes a request and returns a decoded JSON data as an associative array.
     *
     * @param  string $url
     * @param  string $requestMethod
     * @param  array  $params
     *
     * @throws NetPayException
     *
     * @return array
     */
    protected function execute($url, $requestMethod, $key, $params = null)
    {
        $result = $this->_executeCurl($url, $requestMethod, $key, $params);

        // Decode the JSON response as an associative array.
        $array = json_decode($result, true);

        // If response is invalid or not a JSON.
        if (!$this->isValidAPIResponse($array)) {
            throw new Exception('Unknown error. (Bad Response)');
        }

        if (!empty($array['object']) && $array['object'] === 'error') {
            throw NetPayException::getInstance($array);
        }

        return $array;
    }

    /**
     * Checks if response from API was valid.
     *
     * @param  array  $array  - decoded JSON response
     *
     * @return boolean
     */
    protected static function isValidAPIResponse($array)
    {
        return $array && count($array) && isset($array['object']);
    }

    /**
     * @param  string $url
     * @param  string $requestMethod
     * @param  array  $params
     *
     * @throws NetPayException
     *
     * @return string
     */
    private function _executeCurl($url, $requestMethod, $key, $params = null)
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, $this->genOptions($requestMethod, $key . ':', $params));

        // Make a request or thrown an exception.
        if (($result = curl_exec($ch)) === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new Exception($error);
        }

        // Close.
        curl_close($ch);

        return $result;
    }

    /**
     * Creates an option for php-curl from the given request method and parameters in an associative array.
     *
     * @param  string $requestMethod
     * @param  array  $params
     *
     * @return array
     */
    private function genOptions($requestMethod, $userpwd, $params)
    {
        $user_agent = 'NetPayPHP/' . NETPAY_PHP_LIB_VERSION . ' PHP/' . PHP_VERSION;
        $netpay_api_version = defined('NETPAY_API_VERSION') ? NETPAY_API_VERSION : null;

        $options = [
            // Set the HTTP version to 1.1.
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            // Set the request method.
            CURLOPT_CUSTOMREQUEST => $requestMethod,
            // Make php-curl returns the data as string.
            CURLOPT_RETURNTRANSFER => true,
            // Do not include the header in the output.
            CURLOPT_HEADER => false,
            // Track the header request string and set the referer on redirect.
            CURLINFO_HEADER_OUT => true,
            CURLOPT_AUTOREFERER => true,
            // Make HTTP error code above 400 an error.
            // CURLOPT_FAILONERROR => true,
            // Time before the request is aborted.
            CURLOPT_TIMEOUT => $this->NETPAY_TIMEOUT,
            // Time before the request is aborted when attempting to connect.
            CURLOPT_CONNECTTIMEOUT => $this->NETPAY_CONNECTTIMEOUT,
            // Authentication.
            CURLOPT_USERPWD => $userpwd
        ];

        // Config NetPay API Version
        if ($netpay_api_version) {
            $options += [CURLOPT_HTTPHEADER => ['NetPay-Version: ' . $netpay_api_version]];

            $user_agent .= ' NetPayAPI/' . $netpay_api_version;
        }

        // Config UserAgent
        if (defined('NETPAY_USER_AGENT_SUFFIX')) {
            $options += [CURLOPT_USERAGENT => $user_agent . ' ' . NETPAY_USER_AGENT_SUFFIX];
        } else {
            $options += [CURLOPT_USERAGENT => $user_agent];
        }

        // Also merge POST parameters with the option.
        if (is_array($params) && count($params) > 0) {
            $http_query = http_build_query($params);
            $http_query = preg_replace('/%5B\d+%5D/simU', '%5B%5D', $http_query);

            $options += [CURLOPT_POSTFIELDS => $http_query];
        }

        return $options;
    }

    /**
     * Checks whether the resource has been destroyed.
     *
     * @return bool|null
     */
    protected static function isDestroyed()
    {
        $resource = self::getInstance();

        return $resource['deleted'];
    }

    /**
     * Returns the secret key.
     *
     * @return string
     */
    protected static function getResourceKey()
    {
        $resource = self::getInstance();
        if (in_array(get_class($resource), self::$classesToUsePublicKey)) {
            return $resource->_publickey;
        }

        return $resource->_secretkey;
    }
}
