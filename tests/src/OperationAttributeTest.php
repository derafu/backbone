<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsBackbone;

use Attribute;
use Derafu\Backbone\Attribute\Operation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(Operation::class)]
final class OperationAttributeTest extends TestCase
{
    public function testDefaultsLeaveTheMethodsOwnDocumentationInControl(): void
    {
        $operation = new Operation();

        $this->assertSame([
            'name' => null,
            'description' => null,
            'parameters' => [],
            'results' => [],
        ], $operation->toArray());
    }

    public function testReflectionExposesTheDeclaredOperationWithItsMetadataIntact(): void
    {
        $method = new ReflectionMethod(OperationWorkerForTest::class, 'send');
        $attributes = $method->getAttributes(Operation::class);
        $this->assertCount(1, $attributes);
        $operation = $attributes[0]->newInstance();

        $this->assertSame([
            'name' => 'Send document',
            'description' => 'Submits a document.',
            'parameters' => [
                'document' => ['example' => ['id' => 7], 'type' => 'array', 'description' => 'Document data.'],
            ],
            'results' => [
                'success' => ['description' => 'Accepted.', 'example' => ['accepted' => true], 'status' => 202],
            ],
        ], $operation->toArray());
        $this->assertSame([], (new ReflectionMethod(OperationWorkerForTest::class, 'helper'))->getAttributes(Operation::class));
    }

    public function testTheAttributeTargetsMethods(): void
    {
        $attribute = (new ReflectionClass(Operation::class))->getAttributes(Attribute::class)[0]->newInstance();
        $this->assertSame(Attribute::TARGET_METHOD, $attribute->flags);
    }
}

final class OperationWorkerForTest
{
    #[Operation(
        name: 'Send document',
        description: 'Submits a document.',
        parameters: [
            'document' => ['example' => ['id' => 7], 'type' => 'array', 'description' => 'Document data.'],
        ],
        results: [
            'success' => ['description' => 'Accepted.', 'example' => ['accepted' => true], 'status' => 202],
        ]
    )]
    public function send(): void
    {
    }

    public function helper(): void
    {
    }
}
