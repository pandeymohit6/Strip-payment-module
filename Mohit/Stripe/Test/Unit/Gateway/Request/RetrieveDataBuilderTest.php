<?php

namespace Mohit\Stripe\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use Mohit\Stripe\Gateway\Config\Config;
use Mohit\Stripe\Gateway\Helper\SubjectReader;
use Mohit\Stripe\Gateway\Helper\TokenProvider;
use Mohit\Stripe\Gateway\Request\RetrieveDataBuilder;
use Mohit\Stripe\Model\Adapter\StripeAdapter;
use Mohit\Stripe\Observer\DataAssignObserver;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RetrieveDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const CUSTOMER_ID = 'cus_123';
    const PAYMENT_INTENT_ID = 'pi_123';

    /**
     * @var RetrieveDataBuilder
     */
    private $builder;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var StripeAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $adapter;
    
    /**
     * @var TokenProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenProviderMock;

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
    
    /**
     * @var AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressMock;

    protected function setUp()
    {
        $this->paymentDO = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapter = $this->getMockBuilder(StripeAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenProviderMock = $this->getMockBuilder(TokenProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->getMockForAbstractClass(OrderAdapterInterface::class);
        $this->addressMock = $this->getMockForAbstractClass(AddressAdapterInterface::class);

        $this->builder = new RetrieveDataBuilder(
            $this->configMock,
            $this->subjectReaderMock,
            $this->adapter,
            $this->tokenProviderMock
        );
    }

    /**
     * Tests builder with no tokenization
     * 
     * @covers \Mohit\Stripe\Gateway\Request\RetrieveDataBuilder::build
     */
    public function testBuild()
    {
        $additionalData = [
            [ DataAssignObserver::PAYMENT_INTENT, self::PAYMENT_INTENT_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, false ],
        ];

        $expectedResult = [
            RetrieveDataBuilder::PAYMENT_INTENT  => self::PAYMENT_INTENT_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::once())
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * Tests builder with customer store enabled
     * 
     * @covers \Mohit\Stripe\Gateway\Request\RetrieveDataBuilder::build
     */
    public function testBuildStoreCustomer()
    {
        $customerId = rand();
        
        $additionalData = [
            [ DataAssignObserver::PAYMENT_INTENT, self::PAYMENT_INTENT_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, false ],
        ];

        $expectedResult = [
            RetrieveDataBuilder::CUSTOMER => self::CUSTOMER_ID,
            RetrieveDataBuilder::PAYMENT_INTENT => self::PAYMENT_INTENT_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::once())
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->configMock->expects(self::once())
            ->method('isStoreCustomerEnabled')
            ->willReturn(true);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->orderMock->expects(self::exactly(2))
            ->method('getCustomerId')
            ->willReturn($customerId);
        
        $this->tokenProviderMock->expects(self::once())
            ->method('getCustomerStripeId')
            ->willReturn(false);

        $this->orderMock->expects(self::once())
            ->method('getBillingAddress')
            ->willReturn($this->addressMock);

        $customerRequest = $this->prepareCustomerRequest();
        $stripeCustomer = $this->getCustomerObject();

        $this->adapter->expects(self::once())
            ->method('customerCreate')
            ->with($customerRequest)
            ->willReturn($stripeCustomer);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * Tests builder with vault enabled
     * 
     * @covers \Mohit\Stripe\Gateway\Request\RetrieveDataBuilder::build
     */
    public function testBuildVault()
    {
        $customerId = rand();
        
        $additionalData = [
            [ DataAssignObserver::PAYMENT_INTENT, self::PAYMENT_INTENT_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, true ],
        ];

        $expectedResult = [
            RetrieveDataBuilder::CUSTOMER => self::CUSTOMER_ID,
            RetrieveDataBuilder::PAYMENT_INTENT => self::PAYMENT_INTENT_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::exactly(count($additionalData)))
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->orderMock->expects(self::exactly(2))
            ->method('getCustomerId')
            ->willReturn($customerId);
        
        $this->tokenProviderMock->expects(self::once())
            ->method('getCustomerStripeId')
            ->willReturn(false);

        $this->orderMock->expects(self::once())
            ->method('getBillingAddress')
            ->willReturn($this->addressMock);

        $customerRequest = $this->prepareCustomerRequest();
        $stripeCustomer = $this->getCustomerObject();

        $this->adapter->expects(self::once())
            ->method('customerCreate')
            ->with($customerRequest)
            ->willReturn($stripeCustomer);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
    
    /**
     * Tests builder with vault enabled on guest order
     * 
     * @covers \Mohit\Stripe\Gateway\Request\RetrieveDataBuilder::build
     */
    public function testBuildVaultGuest()
    {
        $additionalData = [
            [ DataAssignObserver::PAYMENT_INTENT, self::PAYMENT_INTENT_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, true ],
        ];

        $expectedResult = [
            RetrieveDataBuilder::PAYMENT_INTENT => self::PAYMENT_INTENT_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::once())
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->orderMock->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(null);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
    
    /**
     * Tests builder with vault enabled and customer already
     * saved in Stripe
     * 
     * @covers \Mohit\Stripe\Gateway\Request\RetrieveDataBuilder::build
     */
    public function testBuildVaultExisting()
    {
        $customerId = rand();
        
        $additionalData = [
            [ DataAssignObserver::PAYMENT_INTENT, self::PAYMENT_INTENT_ID ],
            [ VaultConfigProvider::IS_ACTIVE_CODE, true ],
        ];

        $expectedResult = [
            RetrieveDataBuilder::CUSTOMER => self::CUSTOMER_ID,
            RetrieveDataBuilder::PAYMENT_INTENT => self::PAYMENT_INTENT_ID,
        ];

        $buildSubject = [
            'payment' => $this->paymentDO,
        ];

        $this->paymentDO->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDO);

        $this->paymentMock->expects(self::exactly(count($additionalData)))
            ->method('getAdditionalInformation')
            ->willReturnMap($additionalData);
        
        $this->paymentDO->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        
        $this->orderMock->expects(self::exactly(2))
            ->method('getCustomerId')
            ->willReturn($customerId);
        
        $this->tokenProviderMock->expects(self::once())
            ->method('getCustomerStripeId')
            ->willReturn(self::CUSTOMER_ID);
        
        $stripeCustomer = $this->getCustomerObject();
        
        $this->adapter->expects(self::once())
            ->method('customerRetrieve')
            ->with(self::CUSTOMER_ID)
            ->willReturn($stripeCustomer);

        static::assertEquals(
            $expectedResult,
            $this->builder->build($buildSubject)
        );
    }
    
    /**
     * Prepare Stripe customer creation request
     * 
     * @return array
     */
    private function prepareCustomerRequest()
    {
        $email = 'customer@example.com';
        $firstname = 'Name';
        $lastname = 'Surname';
        
        $this->addressMock->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);
        
        $this->addressMock->expects(self::once())
            ->method('getFirstname')
            ->willReturn($firstname);
        
        $this->addressMock->expects(self::once())
            ->method('getLastname')
            ->willReturn($lastname);
        
        return [
            'email' => $email,
            'description' => $firstname . ' ' . $lastname,
        ];
    }
    

    /**
     * Create mock Stripe customer object
     * 
     * @return \stdClass
     */
    private function getCustomerObject()
    {
        return \Stripe\Util\Util::convertToStripeObject([
            'object' => 'customer',
            'id' => self::CUSTOMER_ID,
        ], []);
    }
}
