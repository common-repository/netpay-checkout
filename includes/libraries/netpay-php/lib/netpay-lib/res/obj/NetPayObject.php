<?php

/** @phpstan-consistent-constructor */
class NetPayObject implements ArrayAccess, Iterator, Countable
{
    // Store the attributes of the object.
    protected $_values = [];

    // NetPay secret key.
    protected $_secretkey;

    // NetPay public key.
    protected $_publickey;

    /**
     * Setup the NetPay object. If no secret and public are passed the one defined
     * in config.php will be used.
     *
     * @param string $publickey
     * @param string $secretkey
     */
    protected function __construct($publickey = null, $secretkey = null)
    {
        if ($publickey !== null) {
            $this->_publickey = $publickey;
        } else {
            if (!defined('NETPAY_PUBLIC_KEY')) {
                define('NETPAY_PUBLIC_KEY', 'pkey');
            }
            $this->_publickey = NETPAY_PUBLIC_KEY;
        }

        if ($secretkey !== null) {
            $this->_secretkey = $secretkey;
        } else {
            if (!defined('NETPAY_SECRET_KEY')) {
                define('NETPAY_SECRET_KEY', 'skey');
            }
            $this->_secretkey = NETPAY_SECRET_KEY;
        }

        $this->_values = [];
    }

    /**
     * Reload the object.
     *
     * @param array   $values
     * @param boolean $clear
     */
    #[\ReturnTypeWillChange]
    public function refresh($values, $clear = false)
    {
        if ($clear) {
            $this->_values = [];
        }

        $this->_values = $this->_values ?: [];
        $values = $values ?: [];

        $this->_values = array_merge($this->_values, $values);
    }

    // Override methods of ArrayAccess
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->_values[$key] = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return isset($this->_values[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->_values[$key]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return isset($this->_values[$key]) ? $this->_values[$key] : null;
    }

    // Override methods of Iterator
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->_values);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->_values);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->_values);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        return next($this->_values);
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return ($this->current() !== false);
    }

    // Override methods of Countable
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->_values);
    }

    #[\ReturnTypeWillChange]
    public function toArray()
    {
        return $this->_values;
    }
}
