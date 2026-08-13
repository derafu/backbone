<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Exception;

use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use Throwable;

/**
 * Exception for services not found.
 */
class ServiceNotFoundException extends TranslatableLogicException
{
    /**
     * The constructor is `final` so that `new static(...)` in forService()
     * is guaranteed safe: no subclass (current or future) can override it
     * with an incompatible signature.
     *
     * @param string|array|TranslatableInterface $message
     * @param int $code
     * @param Throwable|null $previous
     */
    final public function __construct(
        string|array|TranslatableInterface $message,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns a new exception for a service not found.
     *
     * Uses `new static(...)` (not `new self(...)`) so that calling this
     * through `parent::forService()` from a subclass factory (e.g.
     * `ComponentNotFoundException::forComponent()`) returns an instance of
     * that subclass, not a plain `ServiceNotFoundException`.
     *
     * @param string $name The name of the service.
     * @param string $type The type of the service (package, component, etc).
     * @return static
     */
    public static function forService(string $name, string $type = 'service'): static
    {
        return new static([
            'The {type} {name} does not exist in the application.',
            'type' => $type,
            'name' => $name,
        ]);
    }
}
