# Implementation Notes

## Resolver Registration

The module uses `etc/graphql/resolver_registry.xml` to register GraphQL resolvers. Depending on your Magento version, you may need to adjust the resolver registration approach:

### Magento 2.4.0 - 2.4.3
May require DI configuration instead. If resolvers are not discovered automatically, update `etc/di.xml`:

```xml
<type name="Magento\Framework\GraphQl\Query\Resolver\FieldResolverRegistry">
    <arguments>
        <argument name="resolvers" xsi:type="array">
            <item name="ProductInterface.hreflang" xsi:type="object">HappyHorizon\HreflangGraphQl\Model\GraphQl\Resolver\Product\Hreflang</item>
            <item name="ProductInterface.alternate_urls" xsi:type="object">HappyHorizon\HreflangGraphQl\Model\GraphQl\Resolver\Product\AlternateUrls</item>
            <item name="CategoryInterface.hreflang" xsi:type="object">HappyHorizon\HreflangGraphQl\Model\GraphQl\Resolver\Category\Hreflang</item>
            <item name="CategoryInterface.alternate_urls" xsi:type="object">HappyHorizon\HreflangGraphQl\Model\GraphQl\Resolver\Category\AlternateUrls</item>
        </argument>
    </arguments>
</type>
```

### Magento 2.4.4+
Should support resolver registry XML files. If not working, use the DI configuration above.

## Testing Resolver Registration

After installation, verify resolvers are registered:

1. **Check GraphQL Schema:**
   ```bash
   bin/magento cache:clean graphql_schema
   ```
   Then query the schema to verify fields exist.

2. **Test Query:**
   ```graphql
   {
     products(filter: {sku: {eq: "test-product"}}) {
       items {
         sku
         hreflang
         alternate_urls
       }
     }
   }
   ```

3. **If fields don't appear:**
   - Check module is enabled: `bin/magento module:status`
   - Verify resolver registry format matches your Magento version
   - Check Magento logs for resolver registration errors

## Canonical URL Integration

When implementing hreflang tags in the frontend:

1. **Canonical URL** should point to the primary language version
2. **Hreflang tags** should reference all alternate language versions
3. **Current page** should have a matching hreflang tag

Example HTML structure:
```html
<link rel="canonical" href="https://example.com/en/product.html" />
<link rel="alternate" hreflang="en" href="https://example.com/en/product.html" />
<link rel="alternate" hreflang="nl" href="https://example.com/nl/product.html" />
<link rel="alternate" hreflang="x-default" href="https://example.com/en/product.html" />
```

## Performance Optimization

If performance becomes an issue with many stores:

1. Consider caching alternate URLs
2. Implement lazy loading for alternate URLs
3. Use GraphQL field selection to only fetch when needed
4. Consider batch loading for multiple products

## Troubleshooting

### Resolvers not working
- Verify module is enabled and upgraded
- Check resolver registry configuration
- Review Magento logs for errors
- Verify schema.graphqls syntax

### URLs not absolute
- Check store base URL configuration
- Verify store emulation is working
- Review HreflangUrlGenerator URL generation logic

### Missing alternate URLs
- Verify product/category availability in stores
- Check website filtering logic
- Review store configuration
- Check logs for availability check failures
