<?php

class NetPayChain extends NetPayApiResource
{
    const ENDPOINT = 'chains';

    /**
     * Retrieves a sub-merchant chain.
     *
     * @param  string $id
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPayChain
     */
    public static function retrieve($id = '', $publickey = null, $secretkey = null)
    {
        return parent::g_retrieve(self::getUrl($id), $publickey, $secretkey);
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::g_reload()
     */
    public function reload()
    {
        if ($this['object'] === 'event') {
            parent::g_reload(self::getUrl($this['id']));
        } else {
            parent::g_reload(self::getUrl());
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::g_revoke()
     */
    public function revoke()
    {
        parent::g_revoke(self::getUrl($this['id']) . '/revoke');
    }

    /**
     * Generate request url.
     *
     * @param  string $id
     *
     * @return string
     */
    private static function getUrl($id = '')
    {
        return NETPAY_API_URL . self::ENDPOINT . '/' . $id;
    }
}
