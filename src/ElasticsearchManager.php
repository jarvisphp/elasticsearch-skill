<?php

declare(strict_types=1);

namespace Jarvis\Skill\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\ElasticsearchException;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ElasticsearchManager
{
    const DEFAULT_INDEX_SETTINGS = [
        'number_of_shards'   => 2,
        'number_of_replicas' => 0,
    ];

    /**
     * @var array
     */
    private $settings;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $stores = [];

    /**
     * Constructor.
     *
     * @param array $settings The Elasticsearch global settings to use
     */
    public function __construct(array $settings, Client $client = null)
    {
        $this->settings = $settings;
        $this->client = $client ?? ClientBuilder::create()->build();
    }

    public function getStore(string $modelClassname): BaseStore
    {
        if (isset($this->stores[$modelClassname])) {
            return $this->stores[$modelClassname];
        }

        $storeClass = $this->settings['model_type_mappings'][$modelClassname]['store_class'] ?? '';
        if (!class_exists($storeClass)) {
            throw new \InvalidArgumentException(sprintf(
                '[%s] failed to find store class for model "%s".',
                __METHOD__,
                $modelClassname
            ));
        }

        if (!is_subclass_of($storeClass, BaseStore::class)) {
            throw new \InvalidArgumentException(sprintf(
                '[%s] store %s must extends class %s',
                __METHOD__,
                $storeClass,
                BaseStore::class
            ));
        }

        return $this->createStore(
            $this->settings['model_type_mappings'][$modelClassname]['index_name'] ?? '',
            $storeClass::getSettings()
        );
    }

    public function isIndexExist(string $name): bool
    {
        $params = [
            'index' => $name,
        ];

        try {
            return $this->client->indices()->exists($params);
        } catch (\ElasticsearchException $exception) {
            throw new \RuntimeException($this->beautifyExceptionMessage($exception, __METHOD__));
        }
    }

    public function createIndex(string $name): void
    {
        if ($this->isIndexExist($name)) {
            return;
        }

        $settings = $this->settings['index']['settings'] ?? self::DEFAULT_INDEX_SETTINGS;
        $params = [
            'index' => $name,
            'body' => [
                'settings' => $settings,
            ]
        ];

        try {
            $this->client->indices()->create($params);
        } catch (ElasticsearchException $exception) {
            throw new \RuntimeException($this->beautifyExceptionMessage($exception, __METHOD__));
        }
    }

    public function deleteIndex(string $name): void
    {
        if (!$this->isIndexExist($name)) {
            return;
        }

        $params = [
            'index' => $name,
        ];

        try {
            $this->client->indices()->delete($params);
        } catch (ElasticsearchException $exception) {
            throw new \RuntimeException($this->beautifyExceptionMessage($exception, __METHOD__));
        }
    }

    /**
     * @param  StoreSettings  $settings
     *
     * @return StoreInterface
     */
    private function createStore(string $indexName, StoreSettings $settings): BaseStore
    {
        $params = [
            'index' => $indexName,
            'type'  => $settings->getType(),
        ];
        if (!$this->client->indices()->existsType($params)) {
            $this->createIndex($indexName);
            $this->client->indices()->putMapping(array_merge($params, [
                'body'  => [
                    $settings->getType() => $settings->getMappings(),
                ],
            ]));
        }

        $storeClass = $settings->getStoreClass();

        return $this->stores[$settings->getModelClass()] = new $storeClass(
            $settings,
            $this->client,
            $indexName
        );
    }

    /**
     * Beautifies ElasticsearchException message.
     *
     * @param  ElasticsearchException $exception The exception that holds the message to beautify
     * @param  string                 $context   The method name from where exception will be throw
     *
     * @return string the beautified message from given exception
     */
    private function beautifyExceptionMessage(ElasticsearchException $exception, string $context): string
    {
        $prefix = sprintf('[%s] ', $context);
        $raw = json_decode($exception->getMessage(), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return $prefix . sprintf(
                '%s has been throw, failed to beautify its message because %s (%d) occured during json_decode()',
                get_class($exception),
                json_last_error(),
                json_last_error_msg()
            );
        }

        return $prefix . sprintf('[%d] %s: %s', $raw['status'], $raw['error']['type'], $raw['error']['reason']);
    }
}
