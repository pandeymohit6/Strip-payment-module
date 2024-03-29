<?php

namespace Mohit\Stripe\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Mohit\Stripe\Gateway\Helper\SubjectReader;

class PaymentIntentIdHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        if ($paymentDO->getPayment() instanceof Payment) {
            /** @var \Stripe\PaymentIntent $paymentIntent */
            $paymentIntent = $this->subjectReader->readPaymentIntent($response);

            /** @var Payment $orderPayment */
            $orderPayment = $paymentDO->getPayment();
            $this->setPaymentIntentId(
                $orderPayment,
                $paymentIntent
            );

            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction());
            $orderPayment->setShouldCloseParentTransaction(
                $this->shouldCloseParentTransaction($orderPayment)
            );
        }
    }

    /**
     * @param Payment $orderPayment
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function setPaymentIntentId(
        Payment $orderPayment,
        \Stripe\PaymentIntent $paymentIntent
    ) {
        $orderPayment->setTransactionId($paymentIntent->id);
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return false;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment)
    {
        return false;
    }
}
