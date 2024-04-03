<?php

declare(strict_types=1);

namespace Happyhorizon\Persistentgraphlqschema\Rewrite\Magento\Framework\Config;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Data extends \magento\framework\Config\Data
{
    /**
     * Configuration reader
     *
     * @var ReaderInterface
     */
    protected $_reader;

    /**
     * Configuration cache
     *
     * @var CacheInterface
     */
    protected $_cache;

    /**
     * Cache tag
     *
     * @var string
     */
    protected $_cacheId;

    /**
     * Cache tags
     *
     * @var array
     */
    protected $cacheTags = [];

    /**
     * Config data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheId;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param ReaderInterface $reader
     * @param CacheInterface $cache
     * @param string $cacheId
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        ReaderInterface     $reader,
        CacheInterface      $cache,
                            $cacheId,
        SerializerInterface $serializer = null
    )
    {
        parent::__construct($reader, $cache, $cacheId);
        $this->reader = $reader;
        $this->cache = $cache;
        $this->cacheId = $cacheId;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
        $this->initData();
    }

    /**
     * Initialise data for configuration
     *
     * @return void
     */
    protected function initData()
    {
        if ($this->cacheId === 'Magento_Framework_GraphQlSchemaStitching_Config_Data') {
            try {
                $filename = realpath(__DIR__ . '/../../../../') . '/app/etc/gql.php';
                $data = \Safe\file_get_contents($filename);
            } catch (\Exception $e) {
                $data = false;
            }

            if (false === $data || '' === (string)$data) {
                $data = $this->reader->read();
                \Safe\file_put_contents($filename, \Safe\json_encode($data));
            } else {
                $data = json_decode($data, true);
            }

            $this->merge($data);

            return;
        }

        $data = $this->cache->load($this->cacheId);
        if (false === $data) {
            $data = $this->reader->read();
            $this->cache->save($this->serializer->serialize($data), $this->cacheId, $this->cacheTags);
        } else {
            $data = $this->serializer->unserialize($data);
        }

        $this->merge($data);
    }
}