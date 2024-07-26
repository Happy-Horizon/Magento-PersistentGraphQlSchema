<?php
declare(strict_types=1);
namespace HappyHorizon\PersistentGraphQlSchema\Block\Cache;

use Magento\Backend\Model\UrlInterface;

class GraphqlFlushButton extends \Magento\Backend\Block\Template
{
    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlBuilder;

    /**
     * @return mixed
     */
    public function getFlushUrl()
    {
        return $this->getUrl('happyhorizon_persistentgraphql/cache/cleanGraphql');
    }
}
