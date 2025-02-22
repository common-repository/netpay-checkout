<?php

class NetPayCardList extends NetPayApiResource
{
    const ENDPOINT = 'cards';

    private $_customerID;

    /**
     * @param array  $cards
     * @param string $customerID
     * @param string $publickey
     * @param string $secretkey
     */
    public function __construct($cards, $customerID, $publickey = null, $secretkey = null)
    {
        parent::__construct($publickey, $secretkey);
        $this->_customerID = $customerID;
        $this->refresh($cards);
    }

    /**
     * retrieve a card
     *
     * @param  string $id
     *
     * @return NetPayCard
     */
    public function retrieve($id)
    {
        $result = parent::execute($this->getUrl($id), parent::REQUEST_GET, self::getResourceKey());

        return new NetPayCard($result, $this->_customerID, $this->_publickey, $this->_secretkey);
    }

    /**
     * @param  string $id
     *
     * @return string
     */
    private function getUrl($id = '')
    {
        return NETPAY_API_URL . 'customers/' . $this->_customerID . '/' . self::ENDPOINT . '/' . $id;
    }
}
