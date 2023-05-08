# Magento HappyHorizon PersistentGraphQlSchema

This module moves the GraphQl Schema caching to his on caching pool/layer.

If the cache type is cleaned or removed the cache data is directly regenerated.
That saves time and resourced for subsequent calls.

Adminhtml cache flush event is hooked to also regenerate the schema after flushing.
