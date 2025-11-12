# Hreflang GraphQL API Validation Guide

This document outlines the validation checklist for ensuring the `hreflang` GraphQL API implementation aligns with Happy Horizon Arnhem's expectations and Horizon Storefront requirements.

## Implementation Overview

The `HappyHorizon_HreflangGraphQl` module provides:
- `hreflang` field: Returns the normalized locale code for the current store (e.g., "en", "nl")
- `alternate_urls` field: Returns comma-separated absolute URLs for all available language versions

## Validation Checklist

### 1. GraphQL Schema Validation

- [ ] Verify that `schema.graphqls` correctly extends `ProductInterface` and `CategoryInterface`
- [ ] Confirm fields are properly documented with `@doc` annotations
- [ ] Test GraphQL introspection query to verify fields appear in schema
- [ ] Ensure field types match expected GraphQL types (`String`)

**Test Query:**
```graphql
{
  __type(name: "ProductInterface") {
    fields {
      name
      description
    }
  }
}
```

### 2. Hreflang Tag Format Validation

- [ ] Verify locale normalization works correctly:
  - "en_US" → "en" ✓
  - "nl_NL" → "nl" ✓
  - "de_DE" → "de" ✓
- [ ] Confirm hreflang values match ISO 639-1 language codes
- [ ] Test with all configured store locales
- [ ] Verify special cases (e.g., "x-default" if needed)

**Expected Behavior:**
- Current store's locale should be normalized to 2-letter language code
- Should handle edge cases gracefully (return empty string on error)

### 3. Alternate URLs Validation

- [ ] Verify all URLs are absolute (start with http:// or https://)
- [ ] Confirm URLs are filtered by website (only same-website stores included)
- [ ] Verify only available products/categories are included:
  - Products: Must be enabled and available
  - Categories: Must be active
- [ ] Test with products/categories available in multiple stores
- [ ] Test with products/categories available in single store
- [ ] Verify URLs are correctly formatted (no double slashes, proper encoding)

**Test Scenarios:**
1. Product available in all stores → Should return URLs for all stores
2. Product available in one store only → Should return single URL
3. Product not available in current website → Should exclude from alternate URLs
4. Category with different visibility per store → Should respect visibility

### 4. Store Emulation Validation

- [ ] Verify store emulation is properly started and stopped
- [ ] Confirm URLs are generated in correct store context
- [ ] Test that emulation doesn't affect other requests
- [ ] Verify error handling when emulation fails

**Key Points:**
- `startEnvironmentEmulation()` must be called before URL generation
- `stopEnvironmentEmulation()` must always be called (even on errors)
- Use try-finally or ensure cleanup in all code paths

### 5. Performance Validation

- [ ] Test with stores containing many languages (5+)
- [ ] Verify response times are acceptable (< 500ms for typical queries)
- [ ] Check for N+1 query issues
- [ ] Verify caching behavior (if applicable)
- [ ] Test with large catalogs

**Performance Targets:**
- Single product query with hreflang fields: < 200ms
- Category query with hreflang fields: < 300ms
- Should not significantly impact overall GraphQL performance

### 6. Integration with Canonical URLs

- [ ] Verify hreflang implementation doesn't conflict with canonical URLs
- [ ] Confirm canonical URL points to primary language version
- [ ] Test that hreflang tags reference all alternate versions
- [ ] Verify current page's hreflang matches its locale
- [ ] Check that canonical and hreflang are consistent

**Best Practices:**
- Canonical URL should be the primary language version
- All hreflang alternate URLs should include the canonical URL
- Current page should have matching hreflang tag

### 7. Error Handling Validation

- [ ] Verify graceful degradation when store is unavailable
- [ ] Test error handling when product/category doesn't exist
- [ ] Confirm proper logging of errors
- [ ] Verify null/empty returns don't break frontend
- [ ] Test with invalid store IDs

**Expected Behavior:**
- Should return `null` or empty string on errors (not throw exceptions)
- Errors should be logged for debugging
- Frontend should handle null/empty values gracefully

### 8. Horizon Storefront Integration

- [ ] Verify GraphQL queries work with Horizon Storefront
- [ ] Test that frontend can consume hreflang and alternate_urls fields
- [ ] Confirm hreflang tags are rendered correctly in HTML
- [ ] Verify alternate URLs are used for `<link rel="alternate">` tags
- [ ] Test multi-language navigation and switching

**Frontend Integration:**
```graphql
query GetProductHreflang($sku: String!) {
  products(filter: { sku: { eq: $sku } }) {
    items {
      sku
      name
      url_key
      hreflang
      alternate_urls
    }
  }
}
```

### 9. Multi-Website Validation

- [ ] Test with multiple websites configured
- [ ] Verify alternate URLs are filtered by website correctly
- [ ] Confirm products from different websites are not included
- [ ] Test website-specific product availability

### 10. Edge Cases

- [ ] Test with single-store setup
- [ ] Test with stores sharing same locale
- [ ] Verify behavior with disabled stores
- [ ] Test with products/categories in root category only
- [ ] Verify behavior with special characters in URLs

## Testing Commands

### Enable Module
```bash
bin/magento module:enable HappyHorizon_HreflangGraphQl
bin/magento setup:upgrade
bin/magento cache:flush
```

### Verify Schema
```bash
bin/magento cache:clean graphql_schema
# Then test GraphQL introspection
```

### Test GraphQL Query
```bash
curl -X POST http://your-magento-url/graphql \
  -H "Content-Type: application/json" \
  -H "Store: default" \
  -d '{
    "query": "query { products(filter: {sku: {eq: \"test-product\"}}) { items { sku hreflang alternate_urls } } }"
  }'
```

## Alignment with Arnhem Checklist

Based on the overlap with "Canonical URL's" from Arnhem's checklist:

1. **Canonical URL Consistency**: Ensure hreflang alternate URLs align with canonical URL structure
2. **URL Format**: Verify URLs match the expected format used by canonical URLs
3. **Store Mapping**: Confirm store-to-locale mapping is consistent across both features
4. **SEO Compliance**: Ensure hreflang tags follow SEO best practices alongside canonical tags

## Issues to Report

If any of the following issues are found, document them:

1. Incorrect locale normalization
2. Missing alternate URLs for available stores
3. URLs from wrong website included
4. Performance degradation
5. Integration issues with Horizon Storefront
6. Conflicts with canonical URL implementation
7. Error handling issues
8. Store emulation problems

## Next Steps After Validation

1. Document any issues found
2. Create tickets for fixes if needed
3. Update implementation based on feedback
4. Re-validate after fixes
5. Get sign-off from Happy Horizon Arnhem
