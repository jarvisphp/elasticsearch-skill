<?php

declare(strict_types=1);

namespace Jarvis\Skill\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
abstract class BaseStore
{
    /**
     * @var StoreSettings
     */
    private $settings;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $indexName;

    /**
     * Gets an instance of StoreSettings which contains store settings.
     *
     * @return StoreSettings the store settings
     */
    abstract public static function getSettings(): StoreSettings;

    /**
     * Constructor.
     *
     * @param StoreSettings $settings
     * @param Client        $client
     * @param string        $indexName
     */
    final public function __construct(StoreSettings $settings, Client $client, string $indexName)
    {
        $this->settings = $settings;
        $this->client = $client;
        $this->indexName = $indexName;
    }

    /**
     * Finds model by its identifier.
     *
     * @param  string $id The identifier to find
     * @return null|
     */
    public function find(string $id): ?ElasticsearchModelInterface
    {
        $response = null;
        $params = $this->computeParams([
            'id' => $id,
        ]);

        try {
            $response = $this->client()->get($params);
        } catch (Missing404Exception $exception) {
            // Nothing to do, model does not exist
            return null;
        }

        if (!$response['found']) {
            return null;
        }

        $source = ['id' => $response['_id']] + $response['_source'];

        return $this->transformToModel($source);
    }

    public function save(ElasticsearchModelInterface $model): void
    {
        $data = $model->dump();
        unset($data['id']);

        $params = $this->computeParams([
            'id'    => $model->getId(),
            'body'  => $data,
        ]);

        $this->client()->index($params);
    }

    public function delete(ElasticsearchModelInterface $model): void
    {
        $params = $this->computeParams(['id' => $model->getId()]);
        $this->client()->delete($params);
    }

    public function search(array $criteria = [], int $start = 0, int $limit = 25, array $sort = []): array
    {
        $params = $this->computeSearchParams($criteria, $start, $limit, $sort);
        $response = $this->client()->search($params);
        $result = $response['hits'];
        if (0 === $result['total']) {
            return [];
        }

        $users = [];
        foreach ($result['hits'] as $hit) {
            $source = ['id' => $hit['_id']] + $hit['_source'];
            $users[] = $this->transformToModel($source);
        }

        return $users;
    }

    /**
     * Returns Elasticsearch client.
     *
     * @return Client the Elasticsearch client.
     */
    final protected function client(): Client
    {
        return $this->client;
    }

    /**
     * [computeParams description]
     *
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    final protected function computeParams(array $params): array
    {
        return array_merge($params, [
            'index' => $this->indexName,
            'type'  => $this->settings->getType(),
        ]);
    }

    final protected function computeSearchParams(array $body, int $start, int $limit, array $rawSort)
    {
        $sort = [];
        foreach ($rawSort as $attribute => $order) {
            $sort[] = sprintf('%s:%s', $attribute, $order);
        }

        $params = [
            'from' => $start,
            'size' => $limit,
            'sort' => $sort,
            'body' => $body,
        ];

        return $this->computeParams($params);
    }

    final protected function transformToModel(array $source): ElasticsearchModelInterface
    {
        $modelClass = $this->settings->getModelClass();

        return $modelClass::restore($source);
    }

    final protected function raiseExceptionOnUnsupportedModel(ElasticsearchModelInterface $model): void
    {
        $modelClass = $this->settings->getModelClass();
        if (!($model instanceof $modelClass)) {
            throw new \LogicException(sprintf(
                '%s is not able to handle %s model',
                static::class,
                get_class($model)
            ));
        }
    }
}
