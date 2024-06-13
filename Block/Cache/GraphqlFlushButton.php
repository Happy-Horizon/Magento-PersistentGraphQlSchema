<?php
declare(strict_types=1);
namespace HappyHorizon\PersistentGraphQlSchema\Block\Cache;

use Magento\Backend\Model\UrlInterface;

class GraphqlFlushButton extends \Magento\Backend\Block\Template
{
    protected UrlInterface $urlBuilder;

    public function getFlushUrl()
    {
        return $this->getUrl('happyhorizon_persistentgraphql/cache/cleanGraphql');
    }
//    /**
//     * @return array
//     */
//    public function getButtonData()
//    {
//        $message = 'Are you sure you want to sync this product? Note that this might take a long time and make this browser tab loading very slow!';
//        $type = 'product';
//        $sku = $this->getProduct()->getSku();
//        $path = 'happyhorizon ' . $type . '/entity_id/' . $sku;
//        boekenwereld_alumioproductsync/index/sync/type/]
//        $url = $this->urlBuilder->getUrl($path);
//        return [
//            'label' => __('Update product'),
//            'on_click' => "confirmSetLocation('{$message}', '{$url}')",
//            'class' => 'action-scalable action-secondary'
//        ];
//    }
}
