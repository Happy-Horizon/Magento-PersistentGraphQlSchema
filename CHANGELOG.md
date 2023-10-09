## 1.2.1 (2023-10-09)

[View Release](git@github.com:Happy-Horizon/Magento-PersistentGraphQlSchema.git/commits/tag/1.2.1)

*  [FEATURE][PWAI-1168] Add try-catch around graphql refresh with info() log on exception. *(Boris van Katwijk)*


## 1.2.0 (2023-10-09)

[View Release](git@github.com:Happy-Horizon/Magento-PersistentGraphQlSchema.git/commits/tag/1.2.0)

*  [FEATURE][PWAI-1168] Refresh persistent graphql schema cache on "php bin/magento cache:flush" aswell. Refactor to PHP 8.1 constructs and make isCacheEnabled a public boolean to check if graphql_schema cache is enabled. *(Boris van Katwijk)*


## 1.1.1 (2023-10-09)

[View Release](git@github.com:Happy-Horizon/Magento-PersistentGraphQlSchema.git/commits/tag/1.1.1)

*  Update title in README.md *(Boris van Katwijk)*
*  [BUGFIX][PWAI-1168] Refresh of graphql schema does not contain dynamic mappers. Rewrite to basic graphql call to make sure the schema is regenerated correctly. *(Boris van Katwijk)*


## 1.1.0 (2023-04-28)

[View Release](git@github.com:Happy-Horizon/Magento-PersistentGraphQlSchema.git/commits/tag/1.1.0)

*  [FEATURE][IN23-11] Simplify cache layer (do not intoduce caching system, but merely separate the schema cache interations). *(Boris van Katwijk)*


## 1.0.0 (2023-04-12)

[View Release](git@github.com:Happy-Horizon/Magento-PersistentGraphQlSchema.git/commits/tag/1.0.0)

*  [FEATURE][IN23-11] Do not silence exceptions, log them instead of throwing. Change cachtag key and identifier. *(Boris van Katwijk)*
*  [FEATURE][IN23-11] Finish base workings for servind "graphql.schema" using a different cache than Magento's "CONFIG" type. Refresh file on cache:flush and cache:clean. Add explanatory comments to clarify working. Update README.md. *(Boris van Katwijk)*
*  [FEATURE][IN23-11] Add "is enabled" check for cache type. Fallback to parent (cache type) if disabled. Updated README.md. *(Boris van Katwijk)*


## 0.4.0 (2023-04-11)

[View Release](git@github.com:Happy-Horizon/Magento-PersistentGraphQlSchema.git/commits/tag/0.4.0)

*  [FEATURE][IN23-11] Initial commit; module for graphql.schema file based persistent caching. *(Boris van Katwijk)*


