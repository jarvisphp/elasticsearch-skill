<?php

declare(strict_types=1);

namespace Jarvis\Skill\Elasticsearch;

use Jarvis\Jarvis;
use Jarvis\Skill\DependencyInjection\ContainerProviderInterface;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ElasticsearchCore implements ContainerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(Jarvis $app)
    {
        $app['elasticsearch_manager'] = function () use ($app): ElasticsearchManager {
            return new ElasticsearchManager($app['elasticsearch.settings'] ?? []);
        };

        $app->lock('elasticsearch_manager');
    }
}
