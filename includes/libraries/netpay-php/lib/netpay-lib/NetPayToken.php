<?php

require_once __DIR__ . '/res/NetPayVaultResource.php';

class NetPayToken extends NetPayVaultResource
{
    const ENDPOINT = 'tokens';

    /**
     * Retrieves a token.
     *
     * @param  string $id
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPayToken
     */
    public static function retrieve($id, $publickey = null, $secretkey = null)
    {
        return parent::g_retrieve(self::getUrl($id), $publickey, $secretkey);
    }

    /**
     * Creates a new token. Please note that this method should be used only
     * in development. In production please use NetPay.js!
     *
     * @param  array  $params
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPayToken
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
        parent::g_reload(self::getUrl($this['id']));
    }

    /**
     * @param  string $id
     *
     * @return string
     */
    private static function getUrl($id = '')
    {
        return NETPAY_VAULT_URL . self::ENDPOINT . '/' . $id;
    }
}
