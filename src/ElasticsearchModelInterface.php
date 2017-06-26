<?php

declare(strict_types=1);

namespace Jarvis\Skill\Elasticsearch;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
interface ElasticsearchModelInterface
{
    public static function restore(array $data): ElasticsearchModelInterface;

    public function getId(): string;

    public function dump(): array;
}
