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
use Derafu\Backbone\Attribute\Package;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\DependencyInjection\ServiceConfigurationCompilerPass;
use Derafu\Backbone\DependencyInjection\ServiceProcessingCompilerPass;
use Derafu\ExamplesBackbone\ExamplePackage;
use Derafu\ExamplesBackbone\ExampleWorker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests that a service tagged by ServiceProcessingCompilerPass gets a
 * `setConfiguration()` method call added, with the configuration resolved
 * from the container parameters at the right nesting level (package,
 * package.components.X, or package.components.X.workers.Y).
 *
 * Runs the real ServiceProcessingCompilerPass first (instead of hand-crafting
 * tags) so the tag shape always matches what production actually produces.
 */
#[CoversClass(ServiceConfigurationCompilerPass::class)]
#[UsesClass(ServiceProcessingCompilerPass::class)]
#[UsesClass(Package::class)]
#[UsesClass(Worker::class)]
#[UsesClass(AbstractServiceMetadata::class)]
class ServiceConfigurationCompilerPassTest extends TestCase
{
    public function testResolvesConfigurationForAPackageLevelService(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('example', [
            'enabled' => true,
        ]);
        $definition = $container->register('app.example_package', ExamplePackage::class);

        (new ServiceProcessingCompilerPass())->process($container);
        (new ServiceConfigurationCompilerPass())->process($container);

        $this->assertTrue($definition->hasMethodCall('setConfiguration'));
        $calls = $definition->getMethodCalls();
        $this->assertSame(['enabled' => true], $calls[0][1][0]);
    }

    public function testResolvesConfigurationForAWorkerNestedInsideItsComponent(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('example', [
            'components' => [
                'example' => [
                    'workers' => [
                        'example' => ['timeout' => 30],
                    ],
                ],
            ],
        ]);
        $definition = $container->register('app.example_worker', ExampleWorker::class)
            ->setArguments([[]]);

        (new ServiceProcessingCompilerPass())->process($container);
        (new ServiceConfigurationCompilerPass())->process($container);

        $this->assertTrue($definition->hasMethodCall('setConfiguration'));
        $calls = $definition->getMethodCalls();
        $this->assertSame(['timeout' => 30], $calls[0][1][0]);
    }

    public function testAddsNoMethodCallWhenTheParameterIsDefinedButEmpty(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('example', []);
        $definition = $container->register('app.example_package', ExamplePackage::class);

        (new ServiceProcessingCompilerPass())->process($container);
        (new ServiceConfigurationCompilerPass())->process($container);

        $this->assertFalse($definition->hasMethodCall('setConfiguration'));
    }

    /**
     * Regression test: a package with no matching parameter defined at all
     * (not even an empty one) used to make ParameterBag::get() throw
     * ParameterNotFoundException, breaking the compilation of the whole
     * container for every package that has no configuration of its own.
     */
    public function testDoesNotFailWhenThePackageHasNoParameterDefinedAtAll(): void
    {
        $container = new ContainerBuilder();
        $definition = $container->register('app.example_package', ExamplePackage::class);

        (new ServiceProcessingCompilerPass())->process($container);
        (new ServiceConfigurationCompilerPass())->process($container);

        $this->assertFalse($definition->hasMethodCall('setConfiguration'));
    }

    public function testUsesTheServicesPrefixToReadTheParameter(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('libredte.lib.example', ['enabled' => true]);
        $definition = $container->register('app.example_package', ExamplePackage::class);

        (new ServiceProcessingCompilerPass('libredte.lib.'))->process($container);
        (new ServiceConfigurationCompilerPass('libredte.lib.'))->process($container);

        $this->assertTrue($definition->hasMethodCall('setConfiguration'));
    }
}
