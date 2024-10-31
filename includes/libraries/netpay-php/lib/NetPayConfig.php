<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright 2020 NetPay. All rights reserved.
 */

namespace NetPay;

class NetPayConfig
{
    //-- Account settings
    public static $PRIVATE_KEY;
    public static $PUBLIC_KEY;

    //-- General settings
    public static $CURLOPT_TIMEOUT; //Timeout in seconds

    public static $API_URL;
    public static $API_URL_LIVE;
    public static $API_URL_SANDBOX;
    public static $TRANSACTION_URL;
    public static $CHECKOUT_URL;
    public static $CASH_URL;
    public static $WEBHOOK_URL;
    public static $CASH_ENABLE_URL;
    public static $CONFIRM_URL;
    public static $CARDINAL_VALIDATE;
    public static $TOKEN_URL;
    public static $MAX_PRODUCTS;
    public static $CARDINAL_AUTH;
    public static $ZONE_AWARE_URL;
    public static $VAULT_URL;
    public static $API_ZONEAWARE_URL;
    public static $API_VAULT_URL;
    public static $URL_VAULT_TEST;
    public static $URL_VAULT_PROD;
    public static $URL_PORT = null;
    public static $CARD_TYPES = [];

    public static $OXXOPAY_ENABLE;
    public static $OXXOPAY_URL;
    public static $TRANSACTION_OXXO_URL;

    public static function init($testMode) {
        self::$API_URL_LIVE = "https://suite.netpay.com.mx/gateway-ecommerce";
        // TEST ENVIRONMENT
        self::$API_URL_SANDBOX = "https://gateway-154.netpaydev.com/gateway-ecommerce";

        // VAULT SERVICE URL TEST
        self::$URL_VAULT_TEST = "https://ks2.api-netpay.com";
        self::$URL_VAULT_PROD = "https://ks2.ntpy.io";
        self::$API_ZONEAWARE_URL = "https://docs.netpay.mx";
        
        //-- General settings
        self::$CURLOPT_TIMEOUT = 180; //Timeout in seconds
        self::$API_VAULT_URL = ($testMode) ? self::$URL_VAULT_TEST : self::$URL_VAULT_PROD;
        self::$API_URL = ($testMode) ? self::$API_URL_SANDBOX : self::$API_URL_LIVE ;
    
        self::$TOKEN_URL = self::$API_URL."/v3/token";
        self::$CHECKOUT_URL = self::$API_URL."/v3.5/charges";
        self::$CONFIRM_URL = self::$API_URL."/v3.5/charges/%s/confirm?processorTransactionId=%s";
        self::$TRANSACTION_URL = self::$API_URL."/v3/transactions/%s";
        self::$CASH_URL = self::$API_URL."/v3/charges";
        self::$CASH_ENABLE_URL = self::$API_URL."/v3/stores";
        self::$CARDINAL_AUTH = self::$API_URL."/v3/cardinal-auth";
        self::$CARDINAL_VALIDATE = self::$API_URL."/v3.5/charges/%s/validate?processorTransactionId=%s&tokenSource=%s";
        self::$WEBHOOK_URL = self::$API_URL."/v3/webhooks/";
        self::$ZONE_AWARE_URL = self::$API_ZONEAWARE_URL."/lookup/ipaddress/";
        self::$VAULT_URL = self::$API_VAULT_URL."/ops/g";
        self::$MAX_PRODUCTS = 300;
        self::$OXXOPAY_ENABLE = self::$API_URL."/v3/config";
        self::$OXXOPAY_URL = self::$API_URL."/v3/oxxopay/reference";
        self::$TRANSACTION_OXXO_URL = self::$API_URL."/v3/oxxopay/transaction/%s";
        
        self::$URL_PORT = null;
        self::$CARD_TYPES = array(
            '001' => 'Visa',
            '002' => 'MasterCard',
            '003' => 'American Express',
        );
    }

}
