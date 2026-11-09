<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Abstract;

use Derafu\Backbone\Contract\ServiceMetadataInterface;

/**
 * Abstract class for the metadata of the services.
 */
abstract class AbstractServiceMetadata implements ServiceMetadataInterface
{
    /**
     * The ID of the service.
     */
    abstract protected string $id { get; }

    /**
     * The name of the service.
     */
    abstract protected string $name { get; }

    /**
     * The description of the service.
     */
    abstract protected ?string $description { get; }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupId(): string
    {
        if ($this->getParentId() === null) {
            return $this->getType()->value;
        }

        return $this->getParentId() . '#' . $this->getType()->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoryId(): string
    {
        return 'service::' . $this->getType()->value;
    }
}
