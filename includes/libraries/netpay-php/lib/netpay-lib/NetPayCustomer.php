<?php

class NetPayCustomer extends NetPayApiResource
{
    const ENDPOINT = 'customers';

    /**
     * Retrieves a customer.
     *
     * @param  string $id
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPayCustomer
     */
    public static function retrieve($id = '', $publickey = null, $secretkey = null)
    {
        return parent::g_retrieve(self::getUrl($id), $publickey, $secretkey);
    }

    /**
     * Search for customers.
     *
     * @param  string $query
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPaySearch
     */
    public static function search($query = '', $publickey = null, $secretkey = null)
    {
        return NetPaySearch::scope('customer', $publickey, $secretkey)->query($query);
    }

    /**
     * Creates a new customer.
     *
     * @param  array  $params
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPayCustomer
     */
    public static function create($params, $publickey = null, $secretkey = null)
    {
        return parent::g_create(self::getUrl(), $params, $publickey, $secretkey);
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::g_reload()
     */
    public function reload()
    {
        if ($this['object'] === 'customer') {
            parent::g_reload(self::getUrl($this['id']));
        } else {
            parent::g_reload(self::getUrl());
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::g_update()
     */
    public function update($params)
    {
        parent::g_update(self::getUrl($this['id']), $params);
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::g_destroy()
     */
    public function destroy()
    {
        parent::g_destroy(self::getUrl($this['id']));
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::isDestroyed()
     */
    public static function isDestroyed()
    {
        return parent::isDestroyed();
    }

    /**
     * Gets a list of all cards belongs to this customer.
     *
     * @param  array $options
     *
     * @return NetPayCardList|null
     */
    public function cards($options = [])
    {
        if (is_array($options) && ! empty($options)) {
            $cards = parent::execute(self::getUrl($this['id']) . '/cards?' . http_build_query($options), parent::REQUEST_GET, parent::getResourceKey());
        } else {
            $cards = $this['cards'];
        }

        return new NetPayCardList($cards, $this['id'], $this->_publickey, $this->_secretkey);
    }

    /**
     * cards() alias
     *
     * @deprecated deprecated since version 2.0.0 use '$customer->cards()'
     *
     * @return     NetPayCardList
     */
    public function getCards($options = [])
    {
        return $this->cards($options);
    }

    /**
     * Gets a list of charge schedules that belongs to a given customer.
     *
     * @param  array|string $options
     *
     * @return NetPayScheduleList|null
     */
    public function schedules($options = [])
    {
        if ($this['object'] === 'customer') {
            if (is_array($options)) {
                $options = '?' . http_build_query($options);
            }

            NetPayScheduleList::g_retrieve(self::getUrl($this['id'] . '/schedules' . $options), $this->_publickey, $this->_secretkey);
        }
    }

    /**
     * @param  string $id
     *
     * @return string
     */
    private static function getUrl($id = '')
    {
        return NETPAY_API_URL . self::ENDPOINT . '/' . $id;
    }
}
