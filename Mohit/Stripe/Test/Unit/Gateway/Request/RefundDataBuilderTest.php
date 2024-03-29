<?php

namespace Mohit\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Mohit\Stripe\Gateway\Config\Config;
use Mohit\Stripe\Gateway\Helper\AmountProvider;
use Mohit\Stripe\Gateway\Helper\SubjectReader;
use Mohit\Stripe\Gateway\Request\RefundDataBuilder;
use Mohit\Stripe\Observer\DataAssignObserver;

class RefundDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const CURRENCY_CODE_DECIMAL = 'USD';
    const CURRENCY_CODE_ZERO_DECIMAL = 'JPY';
    
    /**
     * @var RefundDataBuilder
     */
    private $builder;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    /**
     * @var SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReaderMock;

    /**
     * @var OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    protected function setUp()
    {
        $this->paymentDO = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->getMockForAbstractClass(OrderAdapterInterface::class);

        $this->builder = new RefundDataBuilder(
            new AmountProvider(),
            $this->subjectReaderMock
        );
    }

    /**
     * @covers \Mohit\Stripe\Gateway\Request\RefundDataBuilder::build
     * 
     * @expectedException \InvalidArgumentException
     */
    public function testBuildReadPaymentException()
    {
        $buildSubject = [];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willThrowException(new \InvalidArgumentException());

        $this->builder->build($buildSubject);
    }

    /**
     * @covers \Mohit\Stripe\Gateway\Request\RefundDataBuilder::build
     */
    public function testBuildReadAmountException()
    {
        $paymentIntent = rand();
        $expectedResult = [
            RefundDataBuilder::PAYMENT_INTENT  => $paymentIntent,
            RefundDataBuilder::AMOUNT  => null,
        ];
        
        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readAmount')
            ->with($buildSubject)
            ->willThrowException(new \InvalidArgumentException());

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($paymentIntent);

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_DECIMAL);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * @covers \Mohit\Stripe\Gateway\Request\RefundDataBuilder::build
     */
    public function testBuildDecimal()
    {
        $paymentIntent = rand();
        $expectedResult = [
            RefundDataBuilder::PAYMENT_INTENT => $paymentIntent,
            RefundDataBuilder::AMOUNT  => 1000,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readAmount')
            ->with($buildSubject)
            ->willReturn(10.00);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->paymentMock->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($paymentIntent);

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_DECIMAL);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * @covers \Mohit\Stripe\Gateway\Request\RefundDataBuilder::build
     */
    public function testBuildZeroDecimal()
    {
        $paymentIntent = rand();
        
        $expectedResult = [
            RefundDataBuilder::PAYMENT_INTENT => $paymentIntent,
            RefundDataBuilder::AMOUNT => 1000,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);
        
        $this->subjectReaderMock->expects(self::once())
            ->method('readAmount')
            ->with($buildSubject)
            ->willReturn(1000);

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->paymentMock->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($paymentIntent);

        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn(self::CURRENCY_CODE_ZERO_DECIMAL);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
}
