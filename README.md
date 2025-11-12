# Magento HappyHorizon ShopwareCheckoutGraphQl

This module provides GraphQL integration for Shopware checkout functionality in Magento 2.

## Features

- **Cart Operations**: Retrieve and manage Shopware carts via GraphQL
- **Address Management**: Set and submit shipping/billing addresses
- **Order Placement**: Place orders through Shopware Store API
- **Coupon Codes**: Apply discount codes to carts
- **User Management**: Retrieve Shopware user information

## GraphQL Queries

- `shopwareCart(cartId: String!)`: Retrieve a Shopware cart
- `shopwareUser`: Get current Shopware user information

## GraphQL Mutations

- `shopwareSetAddressInfo`: Set address information on a cart
- `shopwareSubmitAddress`: Submit address information
- `shopwarePlaceOrder`: Place an order from a cart
- `shopwareApplyCouponCode`: Apply a coupon code to a cart

## Configuration

Configure Shopware API credentials in:
**Stores > Configuration > Shopware > API Credentials**

Required settings:
- API URL
- Access Key
- Secret Key
- Sales Channel ID

## Installation

1. Copy the module to `app/code/HappyHorizon/ShopwareCheckoutGraphQl`
2. Run `bin/magento module:enable HappyHorizon_ShopwareCheckoutGraphQl`
3. Run `bin/magento setup:upgrade`
4. Run `bin/magento cache:flush`
5. Configure API credentials in admin panel

## Requirements

- Magento 2.4.x
- PHP 8.0+
- Shopware 6.x with Store API enabled
