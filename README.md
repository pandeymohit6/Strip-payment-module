# Magento 2 Stripe Payments
Stripe payments integration module for Magento 2.

## System requirements
This extension supports the following versions of Magento:
*	Community Edition (CE) versions 2.1.x, 2.2.x and 2.3.x
*	Enterprise Edition (EE) versions 2.1.x, 2.2.x and 2.3.x

## Installation
1. Require the module via Composer
```bash
$ composer require aune-io/magento2-stripe
```

2. Enable the module
```bash
$ bin/magento module:enable Mohit_Stripe
$ bin/magento setup:upgrade
```

3. Login to the admin
4. Go to Stores > Configuration > Sales > Payment Methods > Aune - Stripe
5. Enter your Stripe API Keys and set the payment method as active
6. (Optional) Enable customer storing in Stripe or Vault to allow customers to reuse their payment methods
