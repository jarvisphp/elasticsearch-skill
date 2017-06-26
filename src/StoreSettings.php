<?php

declare(strict_types=1);

namespace Jarvis\Skill\Elasticsearch;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class StoreSettings
{
    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var string
     */
    private $storeClass;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $mappings;

    /**
     * Constructor.
     *
     * @param string $type
     * @param array  $mappings
     *
     * @throws \InvalidArgumentException if provided model class does not exist
     */
    public function __construct(string $modelClass, string $storeClass, string $type, array $mappings)
    {
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException(sprintf(
                'Failed to create StoreSettings, model %s is not a valid class',
                $modelClass
            ));
        }

        $this->modelClass = $modelClass;
        if (!class_exists($storeClass)) {
            throw new \InvalidArgumentException(sprintf(
                'Failed to create StoreSettings, store %s is not a valid class',
                $storeClass
            ));
        }

        $this->storeClass = $storeClass;
        $this->type = $type;
        $this->mappings = $mappings;
    }

    /**
     * Gets store model classname.
     *
     * @return string The model classname
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Gets store classname.
     *
     * @return string The store classname
     */
    public function getStoreClass(): string
    {
        return $this->storeClass;
    }

    /**
     * Gets store type.
     *
     * @return string The store type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets store mappings.
     *
     * @return string The store mappings
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }
}
