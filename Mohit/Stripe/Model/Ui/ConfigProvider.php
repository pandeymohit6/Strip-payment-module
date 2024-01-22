<?php

namespace Mohit\Stripe\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Mohit\Stripe\Gateway\Config\Config;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE       = 'Mohit_Stripe';
    const VAULT_CODE = 'Mohit_Stripe_vault';

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Retrieve checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'publishableKey' => $this->config->getPublishableKey(),
                    'sdkUrl' => $this->config->getSdkUrl(),
                    'ccVaultCode' => self::VAULT_CODE,
                    'paymentIntentUrl' => $this->config->getPaymentIntentUrl(),
                ],
            ]
        ];
    }
}
