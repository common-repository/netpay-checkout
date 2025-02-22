<?php

class NetPayLink extends NetPayApiResource
{
    const ENDPOINT = 'links';

    /**
     * Retrieves a link.
     *
     * @param  string $id
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPayLink
     */
    public static function retrieve($id = '', $publickey = null, $secretkey = null)
    {
        return parent::g_retrieve(self::getUrl($id), $publickey, $secretkey);
    }

    /**
     * Search for links.
     *
     * @param  string $query
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPaySearch
     */
    public static function search($query = '', $publickey = null, $secretkey = null)
    {
        return NetPaySearch::scope('link', $publickey, $secretkey)->query($query);
    }

    /**
     * (non-PHPdoc)
     *
     * @see NetPayApiResource::g_reload()
     */
    public function reload()
    {
        if ($this['object'] === 'link') {
            parent::g_reload(self::getUrl($this['id']));
        } else {
            parent::g_reload(self::getUrl());
        }
    }

    /**
     * Creates a new link.
     *
     * @param  array  $params
     * @param  string $publickey
     * @param  string $secretkey
     *
     * @return NetPayLink
     */
    public static function create($params, $publickey = null, $secretkey = null)
    {
        return parent::g_create(self::getUrl(), $params, $publickey, $secretkey);
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
     * @param  string $id
     *
     * @return string
     */
    private static function getUrl($id = '')
    {
        return NETPAY_API_URL . self::ENDPOINT . '/' . $id;
    }
}
