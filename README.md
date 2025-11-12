# Magento HappyHorizon PersistentGraphQlSchema

This module moves the GraphQl Schema caching to his on caching pool/layer.

If the cache type is cleaned or removed the cache data is directly regenerated.
That saves time and resourced for subsequent calls.

Adminhtml cache flush event is hooked to also regenerate the schema after flushing.

## Alternate URLs (hreflang) Functionality

This module extends the GraphQL schema to provide alternate URLs with hreflang tags for multilingual stores. This functionality helps search engines understand the language and regional targeting of your content.

### Features

- **Multi-store Support**: Automatically generates alternate URLs for all active store views
- **Entity Types Supported**:
  - CMS Pages
  - Products
  - Categories
- **Locale Conversion**: Converts Magento locale codes (e.g., `nl_NL`, `en_US`) to standard hreflang codes (e.g., `nl`, `en`)
- **URL Validation**: Validates generated URLs before returning them
- **Graceful Fallback**: Returns empty array if no alternate URLs are available

### GraphQL Schema Extension

The module extends the following GraphQL types:

```graphql
type AlternateUrl {
    href: String!
    hreflang: String!
}

extend type CmsPage {
    alternate_urls: [AlternateUrl!]
}

extend interface ProductInterface {
    alternate_urls: [AlternateUrl!]
}

extend type CategoryTree {
    alternate_urls: [AlternateUrl!]
}
```

### Usage Example

#### Query CMS Page with Alternate URLs

```graphql
query {
    cmsPage(identifier: "about-us") {
        identifier
        title
        content
        alternate_urls {
            href
            hreflang
        }
    }
}
```

#### Query Product with Alternate URLs

```graphql
query {
    products(filter: { sku: { eq: "product-sku" } }) {
        items {
            sku
            name
            url_key
            alternate_urls {
                href
                hreflang
            }
        }
    }
}
```

#### Query Category with Alternate URLs

```graphql
query {
    category(id: 2) {
        id
        name
        url_key
        alternate_urls {
            href
            hreflang
        }
    }
}
```

### Response Format

The `alternate_urls` field returns an array of objects with the following structure:

```json
[
    {
        "href": "https://example.com/nl/about-us",
        "hreflang": "nl"
    },
    {
        "href": "https://example.com/en/about-us",
        "hreflang": "en"
    }
]
```

### Data Format

- **href**: The full URL of the page in the alternate store view
- **hreflang**: The language code in ISO 639-1 format (e.g., `nl`, `en`, `de`)

### How It Works

1. The resolver retrieves all active store views from Magento
2. For each store view (excluding the current one), it:
   - Determines the entity URL in that store view
   - Converts the store's locale to a hreflang code
   - Validates the URL
   - Adds it to the result array
3. Returns an empty array if no valid alternate URLs are found

### Locale to Hreflang Conversion

The module converts Magento locale codes to standard hreflang codes:

- `nl_NL` → `nl`
- `en_US` → `en`
- `de_DE` → `de`
- `fr_FR` → `fr`
- `es_ES` → `es`
- `it_IT` → `it`

Custom locales are converted by extracting the language code (first part before underscore).

### Requirements

- Magento 2.3+
- Multiple store views configured
- GraphQL module enabled

### Installation

1. Copy the module files to `app/code/HappyHorizon/PersistentGraphQlSchema/`
2. Run `bin/magento setup:upgrade`
3. Run `bin/magento cache:clean`
4. Run `bin/magento setup:di:compile`

### Configuration

No additional configuration is required. The module automatically:
- Detects all active store views
- Uses the locale configuration from each store view
- Generates URLs based on Magento's URL rewrite system

### Notes

- Only active store views are included in the alternate URLs
- The current store view is excluded from the alternate URLs list
- URLs are validated before being returned
- If an entity doesn't exist in a store view, that store view is skipped
- CMS pages must be active to appear in alternate URLs
- Products must be available (enabled and in stock) to appear in alternate URLs
- Categories must be active to appear in alternate URLs
