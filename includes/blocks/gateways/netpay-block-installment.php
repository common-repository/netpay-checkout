<?php

class NetPay_Block_Installment extends NetPay_Block_Payment {
    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'netpay_installment';

    public function set_additional_data() {
        $viewData = [];
        $installment_backends = [];

        foreach($viewData['installment_backends'] as $backend) {
            $installment_backends[] = (array)$backend;
        }

        $this->additional_data = [
            'is_zero_interest' => $viewData['is_zero_interest'],
            'installment_min_limit' => $viewData['installment_min_limit'],
            'installment_backends' => $installment_backends,
        ];
    }
}
