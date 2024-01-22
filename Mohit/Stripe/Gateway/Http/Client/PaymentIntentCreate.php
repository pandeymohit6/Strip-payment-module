<?php

namespace Mohit\Stripe\Gateway\Http\Client;

class PaymentIntentCreate extends AbstractClient
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        return $this->adapter->paymentIntentCreate($data);
    }
}
