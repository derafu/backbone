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

use Derafu\Backbone\Abstract\AbstractHandler;
use Derafu\Backbone\Abstract\AbstractService;
use Derafu\Backbone\Abstract\AbstractServiceMetadata;
use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Handler;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\ServiceInterface;
use Derafu\Backbone\DependencyInjection\ServiceProcessingCompilerPass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(ServiceProcessingCompilerPass::class)]
#[CoversClass(Worker::class)]
#[CoversClass(Handler::class)]
#[CoversClass(Strategy::class)]
#[UsesClass(AbstractServiceMetadata::class)]
final class ServiceDiscoveryTest extends TestCase
{
    /**
     * @return array<string, array{class-string<ServiceInterface>, string, string, array<string, string|null>}>
     */
    public static function services(): array
    {
        return [
            'worker' => [
                DiscoveryWorker::class, 'billing.document.builder', 'billing.document#worker',
                ['type' => 'worker', 'id' => 'billing.document.builder', 'name' => 'builder',
                    'component' => 'document', 'package' => 'billing', 'description' => 'Builds documents.'],
            ],
            'handler' => [
                DiscoveryHandler::class, 'billing.document.builder.handler:send', 'billing.document.builder#handler',
                ['type' => 'handler', 'id' => 'billing.document.builder.handler:send', 'name' => 'send',
                    'worker' => 'builder', 'component' => 'document', 'package' => 'billing', 'description' => null],
            ],
            'strategy' => [
                DiscoveryStrategy::class, 'billing.document.builder.strategy:xml', 'billing.document.builder#strategy',
                ['type' => 'strategy', 'id' => 'billing.document.builder.strategy:xml', 'name' => 'xml',
                    'worker' => 'builder', 'component' => 'document', 'package' => 'billing', 'description' => 'XML format.'],
            ],
        ];
    }

    /**
     * @param class-string<ServiceInterface> $class
     * @param array<string, string|null> $metadata
     */
    #[DataProvider('services')]
    public function testDiscoversServicesWithPrivatePrefixedAliasesAndCompleteTags(
        string $class,
        string $id,
        string $group,
        array $metadata
    ): void {
        $container = new ContainerBuilder();
        $definition = $container->register('app.service', $class);
        (new ServiceProcessingCompilerPass('app.'))->process($container);

        $alias = $container->getAlias('app.' . $id);
        $this->assertSame('app.service', (string) $alias);
        $this->assertFalse($alias->isPublic());
        $this->assertTrue($definition->isLazy());
        $this->assertSame([$metadata], $definition->getTag($group));
        $this->assertSame([$metadata], $definition->getTag('service::' . $metadata['type']));
    }

    public function testLeavesAnActualServiceWithoutSupportedMetadataUntouched(): void
    {
        $container = new ContainerBuilder();
        $definition = $container->register('app.bare', BareDiscoveryService::class);
        $missing = $container->register('app.missing', 'MissingDiscoveryServiceClass');

        (new ServiceProcessingCompilerPass())->process($container);

        $this->assertSame([], $container->getAliases());
        $this->assertSame([], $definition->getTags());
        $this->assertFalse($definition->isLazy());
        $this->assertFalse($missing->isLazy());
    }
}

#[Worker(name: 'builder', component: 'document', package: 'billing', description: 'Builds documents.')]
final class DiscoveryWorker extends AbstractWorker
{
}

#[Handler(name: 'send', worker: 'builder', component: 'document', package: 'billing')]
final class DiscoveryHandler extends AbstractHandler
{
}

#[Strategy(name: 'xml', worker: 'builder', component: 'document', package: 'billing', description: 'XML format.')]
final class DiscoveryStrategy extends AbstractStrategy
{
}

final class BareDiscoveryService extends AbstractService
{
}
