<?php

namespace Mohit\Stripe\Gateway\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Mohit\Stripe\Model\Adapter\StripeAdapter;

class TokenProvider
{
    const ATTRIBUTE_CODE = 'stripe_id';
    const TOKEN_TYPE_CUSTOMER = 'customer';
    const TOKEN_TYPE_SOURCE = 'source';
    
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    
    /**
     * @var StripeAdapter
     */
    private $stripeAdapter;
    
    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param StripeAdapter $stripeAdapter
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        StripeAdapter $stripeAdapter
    ) {
        $this->customerRepository = $customerRepository;
        $this->stripeAdapter = $stripeAdapter;
    }
    
    /**
     * Returns the Stripe customer id given the Magento customer id
     */
    public function getCustomerStripeId(int $magentoCustomerId)
    {
        try {
            $customer = $this->customerRepository->getById($magentoCustomerId);
            $attribute = $customer->getCustomAttribute(self::ATTRIBUTE_CODE);
            if ($attribute instanceof \Magento\Framework\Api\AttributeValue) {
                return $attribute->getValue();
            }
        } catch (NoSuchEntityException $ex) {
            return null;
        }
    }
    
    /**
     * Saves the Stripe customer id against a Magento customer
     */
    public function setCustomerStripeId(int $magentoCustomerId, string $stripeCustomerId)
    {
        try {
            $customer = $this->customerRepository->getById($magentoCustomerId);
            $customer->setCustomAttribute(self::ATTRIBUTE_CODE, $stripeCustomerId);
            return $this->customerRepository->save($customer);
        } catch (NoSuchEntityException $ex) {
            return false;
        }
    }
}
