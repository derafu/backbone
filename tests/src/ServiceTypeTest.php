<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsBackbone;

use Derafu\Backbone\Attribute\Component;
use Derafu\Backbone\Attribute\Handler;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Attribute\Package;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Enum\ServiceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServiceType::class)]
class ServiceTypeTest extends TestCase
{
    #[TestWith([ServiceType::PACKAGE, Package::class])]
    #[TestWith([ServiceType::COMPONENT, Component::class])]
    #[TestWith([ServiceType::WORKER, Worker::class])]
    #[TestWith([ServiceType::JOB, Job::class])]
    #[TestWith([ServiceType::HANDLER, Handler::class])]
    #[TestWith([ServiceType::STRATEGY, Strategy::class])]
    public function testEachCaseMapsToItsAttributeClass(ServiceType $case, string $expectedClass): void
    {
        $this->assertSame($expectedClass, $case->getAttributeClass());
    }
}
