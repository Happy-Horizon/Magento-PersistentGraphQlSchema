# Mage2 Module HappyHorizon PersistentGraphQlSchema

This module moves the GraphQl Schema caching to his on caching pool.
This simple cache types creates a simple file containing the graphql schema cache

File location
var/graphql.schema

If the cache type is cleaned or removed the cache data is directly regenerated. 

Recreating the schema data can also be forced by deleting the var/graphql.schema file.
The cache clean method has the advantage that it swaps the files and there should be no downtime. 

# To do
Check if the cache is enabled/disabled?

$cacheState = $om->get('Magento\Framework\App\Cache\StateInterface');
/** @var bool $isEnabled */
$isEnabled = $cacheState->isEnabled(
\Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER
);

