<?php
if (! class_exists('NetPayPluginHelperCharge')) {
    class NetPayPluginHelperCharge
    {
        /**
         * @param \netpay-php\NetPayCharge $charge
         * @return boolean
         */
        public static function isChargeObject($charge)
        {
            if (! isset($charge['object']) || $charge['object'] !== 'charge') {
                return false;
            }
            return true;
        }

        /**
         * @param \netpay-php\NetPayCharge $charge
         * @return boolean
         */
        public static function isAuthorized($charge)
        {
            return self::isChargeObject($charge) && $charge['authorized'];
        }

        /**
         * @param \netpay-php\NetPayCharge $charge
         * @return boolean
         */
        public static function isPaid($charge)
        {
            return self::isChargeObject($charge) && $charge['paid'];
        }

        /**
         * @param \netpay-php\NetPayCharge $charge
         * @return boolean
         */
        public static function isFailed($charge)
        {
            if (! self::isChargeObject($charge)) {
                return true;
            }
                
            if ((! is_null($charge['failure_code']) && $charge['failure_code'] !== "")
                || (! is_null($charge['failure_message']) && $charge['failure_message'] !== "")) {
                return true;
            }

            if (strtoupper($charge['status']) === 'FAILED') {
                return true;
            }

            return false;
        }
    }
}