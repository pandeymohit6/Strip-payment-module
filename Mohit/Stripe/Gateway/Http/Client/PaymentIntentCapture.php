<?php

namespace Mohit\Stripe\Gateway\Http\Client;

use Mohit\Stripe\Gateway\Request\CaptureDataBuilder;

class PaymentIntentCapture extends AbstractClient
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $paymentIntentId = $data[CaptureDataBuilder::PAYMENT_INTENT];
        unset($data[CaptureDataBuilder::PAYMENT_INTENT]);

        $paymentIntent = $this->adapter->paymentIntentRetrieve($paymentIntentId);
        $paymentIntent->capture($data);

        return $paymentIntent;
    }
}
