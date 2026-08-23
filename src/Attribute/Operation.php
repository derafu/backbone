<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Attribute;

use Attribute;

/**
 * Marks a public method of a `Worker` as a real, dispatchable operation.
 *
 * A worker's public methods otherwise include whatever its class hierarchy
 * happens to expose (trait helpers, `ServiceInterface::getName()`, etc.),
 * not just its actual business methods. Tagging the real ones explicitly
 * is what lets any consumer draw a reliable line between the two — an
 * attribute-based allow-list, generated documentation, or anything else
 * that needs to enumerate a worker's genuine capabilities without guessing
 * from method visibility alone.
 *
 * Deliberately `TARGET_METHOD`, not `TARGET_CLASS`: unlike `Job`/`Handler`/
 * `Strategy`, an operation is not its own service with its own identity or
 * lifecycle — it is a capability of a `Worker` that already exists. For
 * that reason this does not extend `AbstractServiceMetadata` or implement
 * `ServiceMetadataInterface` (both model registrable services, not method
 * capabilities), and has no `id`/`getParentId()` of its own.
 *
 * Every property here is optional and exists only to say something
 * reflection/PHPDoc cannot:
 *
 *   - `$name`/`$description` override the method's own PHPDoc summary/
 *     description, for the (expected to be rare) case where the text
 *     written for PHP maintainers isn't the text an external API consumer
 *     should see. Leave both `null` to keep using PHPDoc, which is the
 *     normal case — this is not a place to duplicate what a docblock
 *     already says.
 *   - `$parameters` overrides/extends what reflection already knows about
 *     each parameter (`name`/`type`/`required`/`default`), keyed by
 *     parameter name: `'example'` (a realistic sample value — reflection
 *     has no way to produce one), and, when reflection's own type isn't
 *     precise enough (a union type collapsed to a string, a bare `array`
 *     with a real shape), `'type'`/`'description'` to override it. Only
 *     the keys present are applied — omitting `'type'` keeps the reflected
 *     one.
 *   - `$results` documents outcomes, keyed however the consumer identifies
 *     each one — e.g. `['success' => ['description' => ..., 'example' =>
 *     ...]]` — with whatever data that consumer finds useful under each
 *     key. This attribute does not define what a key means; it just holds
 *     what's given.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Operation
{
    /**
     * Constructor.
     *
     * @param string|null $name Overrides the PHPDoc summary, if given.
     * @param string|null $description Overrides the PHPDoc description, if
     * given.
     * @param array $parameters Per-parameter overrides/additions, keyed by
     * parameter name: `['example' => ..., 'type' => ..., 'description' =>
     * ...]`, all optional.
     * @param array $results Documented outcomes, keyed however the
     * consumer identifies each one.
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly array $parameters = [],
        public readonly array $results = [],
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => $this->parameters,
            'results' => $this->results,
        ];
    }
}
