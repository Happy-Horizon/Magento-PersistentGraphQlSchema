# HappyHorizon Hreflang GraphQL Module

This module provides GraphQL API support for `hreflang` tags and alternate URLs for multi-language webshop functionality.

## Overview

The module extends Magento's GraphQL API to include `hreflang` and `alternate_urls` fields on `ProductInterface` and `CategoryInterface`. This enables frontend applications (like Horizon Storefront) to properly implement hreflang tags for SEO and multi-language support.

## Features

- **Hreflang Tag Support**: Returns the normalized locale code (e.g., "en", "nl") for the current store
- **Alternate URLs**: Generates absolute URLs for all available language versions of products and categories
- **Store Emulation**: Properly handles store emulation to generate accurate URLs
- **Website Filtering**: Filters alternate URLs by website to ensure only relevant stores are included
- **Availability Checking**: Validates that products and categories are available in each store before including them
- **Locale Normalization**: Converts locale codes from format "en_US" to "en" for hreflang compliance

## GraphQL Schema Extensions

The module adds the following fields to GraphQL queries:

### ProductInterface
- `hreflang: String` - The hreflang tag value for the current product
- `alternate_urls: String` - Comma-separated list of alternate URLs for the product

### CategoryInterface
- `hreflang: String` - The hreflang tag value for the current category
- `alternate_urls: String` - Comma-separated list of alternate URLs for the category

## Usage Example

```graphql
query {
  products(filter: { sku: { eq: "test-product" } }) {
    items {
      sku
      name
      hreflang
      alternate_urls
    }
  }
}
```

## Implementation Details

### HreflangUrlGenerator

The core class `HappyHorizon\HreflangGraphQl\Model\HreflangUrlGenerator` handles:
- Absolute URL generation for products and categories
- Locale normalization (e.g., "en_US" → "en")
- Store emulation for accurate URL generation
- Filtering by website and product/category availability

### Resolvers

Four GraphQL resolvers are implemented:
- `Product/Hreflang.php` - Resolves hreflang for products
- `Product/AlternateUrls.php` - Resolves alternate URLs for products
- `Category/Hreflang.php` - Resolves hreflang for categories
- `Category/AlternateUrls.php` - Resolves alternate URLs for categories

## Integration with Canonical URLs

This module works alongside Magento's canonical URL functionality. When implementing hreflang tags in the frontend, ensure that:
1. The canonical URL points to the primary language version
2. Hreflang tags reference all alternate language versions
3. The current page's hreflang tag matches its locale

## Requirements

- Magento 2.4.x or higher
- PHP 8.0 or higher
- Magento GraphQL module
- Magento Catalog GraphQL module

## Installation

1. Copy the module to `app/code/HappyHorizon/HreflangGraphQl/`
2. Run `bin/magento module:enable HappyHorizon_HreflangGraphQl`
3. Run `bin/magento setup:upgrade`
4. Run `bin/magento cache:flush`

## Validation Checklist

When validating this implementation with Happy Horizon Arnhem, ensure:

- [ ] Hreflang tags are correctly formatted (e.g., "en", "nl", "x-default")
- [ ] Alternate URLs are absolute URLs
- [ ] Only available products/categories in each store are included
- [ ] URLs are filtered by website correctly
- [ ] Locale normalization works correctly for all configured locales
- [ ] Integration with canonical URLs is properly handled
- [ ] Performance is acceptable for stores with many languages

## Support

For issues or questions, contact Happy Horizon development team.
