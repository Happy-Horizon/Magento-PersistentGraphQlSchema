# HappyHorizon CustomerAddressGraphQl Module

This module provides GraphQL mutations for managing customer addresses in Magento 2.

## Features

- **setDefaultAddress mutation**: Allows authenticated customers to set their default shipping or billing address.

## Installation

1. Copy the module to `app/code/HappyHorizon/CustomerAddressGraphQl/`
2. Run `bin/magento module:enable HappyHorizon_CustomerAddressGraphQl`
3. Run `bin/magento setup:upgrade`
4. Run `bin/magento cache:flush`

## GraphQL Usage

### setDefaultAddress Mutation

Set a default shipping or billing address for the authenticated customer.

**Mutation:**
```graphql
mutation {
    setDefaultAddress(
        addressId: 123
        addressType: SHIPPING
    ) {
        success
        message
    }
}
```

**Parameters:**
- `addressId` (Int!, required): The ID of the address to set as default
- `addressType` (AddressType!, required): Either `SHIPPING` or `BILLING`

**Response:**
- `success` (Boolean!): Indicates whether the operation was successful
- `message` (String): Response message

**Example Response:**
```json
{
    "data": {
        "setDefaultAddress": {
            "success": true,
            "message": "Default shipping address has been updated successfully."
        }
    }
}
```

## Requirements

- Magento 2.4.x or higher
- PHP 8.0 or higher
- Magento Customer module
- Magento GraphQL module

## License

OSL-3.0
