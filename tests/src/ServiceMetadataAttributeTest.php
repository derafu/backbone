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
use PHPUnit\Framework\TestCase;

/**
 * Tests the id/parent-id/group-id/category-id computation and toArray() of
 * every service metadata attribute. This is the data that
 * ServiceProcessingCompilerPass relies on to build aliases and tags, so a
 * regression here would silently break auto-discovery.
 */
#[CoversClass(Package::class)]
#[CoversClass(Component::class)]
#[CoversClass(Worker::class)]
#[CoversClass(Job::class)]
#[CoversClass(Handler::class)]
#[CoversClass(Strategy::class)]
class ServiceMetadataAttributeTest extends TestCase
{
    public function testPackage(): void
    {
        $package = new Package(name: 'billing', description: 'Billing package.');

        $this->assertSame('billing', $package->id);
        $this->assertNull($package->getParentId());
        $this->assertSame(ServiceType::PACKAGE, $package->getType());
        $this->assertSame('package', $package->getGroupId());
        $this->assertSame('service::package', $package->getCategoryId());
        $this->assertSame([
            'type' => 'package',
            'id' => 'billing',
            'name' => 'billing',
            'description' => 'Billing package.',
        ], $package->toArray());
    }

    public function testComponent(): void
    {
        $component = new Component(name: 'document', package: 'billing');

        $this->assertSame('billing.document', $component->id);
        $this->assertSame('billing', $component->getParentId());
        $this->assertSame(ServiceType::COMPONENT, $component->getType());
        $this->assertSame('billing#component', $component->getGroupId());
        $this->assertSame('service::component', $component->getCategoryId());
        $this->assertSame([
            'type' => 'component',
            'id' => 'billing.document',
            'name' => 'document',
            'package' => 'billing',
            'description' => null,
        ], $component->toArray());
    }

    public function testWorker(): void
    {
        $worker = new Worker(name: 'builder', component: 'document', package: 'billing');

        $this->assertSame('billing.document.builder', $worker->id);
        $this->assertSame('billing.document', $worker->getParentId());
        $this->assertSame(ServiceType::WORKER, $worker->getType());
        $this->assertSame('billing.document#worker', $worker->getGroupId());
        $this->assertSame('service::worker', $worker->getCategoryId());
    }

    public function testJob(): void
    {
        $job = new Job(
            name: 'build',
            worker: 'builder',
            component: 'document',
            package: 'billing'
        );

        $this->assertSame('billing.document.builder.job:build', $job->id);
        $this->assertSame('billing.document.builder', $job->getParentId());
        $this->assertSame(ServiceType::JOB, $job->getType());
        $this->assertSame('billing.document.builder#job', $job->getGroupId());
        $this->assertSame('service::job', $job->getCategoryId());
        $this->assertSame([
            'type' => 'job',
            'id' => 'billing.document.builder.job:build',
            'name' => 'build',
            'worker' => 'builder',
            'component' => 'document',
            'package' => 'billing',
            'description' => null,
        ], $job->toArray());
    }

    public function testHandler(): void
    {
        $handler = new Handler(
            name: 'default',
            worker: 'builder',
            component: 'document',
            package: 'billing'
        );

        $this->assertSame('billing.document.builder.handler:default', $handler->id);
        $this->assertSame(ServiceType::HANDLER, $handler->getType());
        $this->assertSame('service::handler', $handler->getCategoryId());
    }

    public function testStrategy(): void
    {
        $strategy = new Strategy(
            name: 'xml',
            worker: 'builder',
            component: 'document',
            package: 'billing'
        );

        $this->assertSame('billing.document.builder.strategy:xml', $strategy->id);
        $this->assertSame(ServiceType::STRATEGY, $strategy->getType());
        $this->assertSame('service::strategy', $strategy->getCategoryId());
    }
}
