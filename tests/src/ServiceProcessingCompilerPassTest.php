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

use Derafu\Backbone\Abstract\AbstractServiceMetadata;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Attribute\Package;
use Derafu\Backbone\DependencyInjection\ServiceProcessingCompilerPass;
use Derafu\ExamplesBackbone\ExampleJob;
use Derafu\ExamplesBackbone\ExamplePackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the core auto-discovery mechanism of the package: turning a
 * service's metadata attribute (e.g. #[Job(...)]) into a container alias
 * and tags. Uses real Symfony ContainerBuilder/Definition objects (no
 * mocks) and the real example classes already shipped with the package.
 */
#[CoversClass(ServiceProcessingCompilerPass::class)]
#[UsesClass(Job::class)]
#[UsesClass(Package::class)]
#[UsesClass(AbstractServiceMetadata::class)]
class ServiceProcessingCompilerPassTest extends TestCase
{
    public function testCreatesAnAliasAndTagsForAServiceWithAJobAttribute(): void
    {
        $container = new ContainerBuilder();
        $definition = $container->register('app.example_job', ExampleJob::class);

        (new ServiceProcessingCompilerPass())->process($container);

        $this->assertTrue($container->hasAlias('example.example.example.job:example'));
        $alias = $container->getAlias('example.example.example.job:example');
        $this->assertSame('app.example_job', (string) $alias);

        $this->assertTrue($definition->hasTag('example.example.example#job'));
        $this->assertTrue($definition->hasTag('service::job'));
        $this->assertTrue($definition->isLazy());
    }

    public function testMakesThePackageAliasPublicButOtherAliasesPrivate(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.example_job', ExampleJob::class);
        $container->register('app.example_package', ExamplePackage::class);

        (new ServiceProcessingCompilerPass())->process($container);

        $this->assertTrue($container->getAlias('example')->isPublic());
        $this->assertFalse($container->getAlias('example.example.example.job:example')->isPublic());
    }

    public function testAddsTheServicesPrefixToTheGeneratedAlias(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.example_package', ExamplePackage::class);

        (new ServiceProcessingCompilerPass('libredte.lib.'))->process($container);

        $this->assertTrue($container->hasAlias('libredte.lib.example'));
        $this->assertFalse($container->hasAlias('example'));
    }

    public function testDoesNothingForAServiceWithoutAMetadataAttribute(): void
    {
        $container = new ContainerBuilder();
        $definition = $container->register('app.plain_service', stdClass::class);

        (new ServiceProcessingCompilerPass())->process($container);

        $this->assertSame([], $container->getAliases());
        $this->assertFalse($definition->isLazy());
    }

    public function testSkipsSyntheticAndAbstractDefinitions(): void
    {
        $container = new ContainerBuilder();
        $synthetic = $container->register('app.synthetic', ExampleJob::class)->setSynthetic(true);
        $abstract = $container->register('app.abstract', ExampleJob::class)->setAbstract(true);

        (new ServiceProcessingCompilerPass())->process($container);

        $this->assertFalse($synthetic->isLazy());
        $this->assertFalse($abstract->isLazy());
        $this->assertSame([], $container->getAliases());
    }
}
