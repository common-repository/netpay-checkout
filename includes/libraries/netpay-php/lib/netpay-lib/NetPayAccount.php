<?php

class NetPayAccount extends NetPayApiResource
{
    const ENDPOINT = 'account';

    /**
     * Retrieves an account.
     *
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPayAccount
     */
    public static function retrieve($publickey = null, $secretkey = null)
    {
        return parent::g_retrieve(self::getUrl(), $publickey, $secretkey);
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::g_update()
     */
    public function update($params)
    {
        parent::g_update(self::getUrl(), $params);
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::g_reload()
     */
    public function reload()
    {
        parent::g_reload(self::getUrl());
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
