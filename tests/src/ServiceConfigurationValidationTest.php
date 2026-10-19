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

use Derafu\Backbone\Abstract\AbstractServiceMetadata;
use Derafu\Backbone\Attribute\Component;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\DependencyInjection\ServiceConfigurationCompilerPass;
use Derafu\Backbone\DependencyInjection\ServiceProcessingCompilerPass;
use Derafu\Backbone\Exception\ServiceConfigurationException;
use Derafu\ExamplesBackbone\ExampleComponent;
use Derafu\ExamplesBackbone\ExamplePackage;
use Derafu\ExamplesBackbone\ExampleWorker;
use Derafu\Translation\Contract\TranslatableInterface;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Configuration paths preserve valid values and reject incompatible sections.
 */
#[CoversClass(ServiceConfigurationCompilerPass::class)]
#[CoversClass(ServiceConfigurationException::class)]
#[UsesClass(ServiceProcessingCompilerPass::class)]
#[UsesClass(Component::class)]
#[UsesClass(Worker::class)]
#[UsesClass(AbstractServiceMetadata::class)]
final class ServiceConfigurationValidationTest extends TestCase
{
    public function testResolvesComponentConfigurationWithoutValidatingUnvisitedValues(): void
    {
        $configuration = [
            'enabled' => false,
            'timeout' => 30,
            'workers' => 'not traversed for a component',
        ];
        $container = new ContainerBuilder();
        $container->setParameter('example', [
            'components' => [
                'example' => $configuration,
                'unrelated' => 'not traversed',
            ],
        ]);
        $definition = $container->register('app.component', ExampleComponent::class);

        (new ServiceProcessingCompilerPass())->process($container);
        (new ServiceConfigurationCompilerPass())->process($container);

        $this->assertSame([
            ['setConfiguration', [$configuration]],
        ], $definition->getMethodCalls());
    }

    public function testSkipsMissingClassesAndServicesThatAreNotConfigurable(): void
    {
        $container = new ContainerBuilder();
        $missing = $container->register('app.missing', 'MissingConfigurationTestClass');
        $plain = $container->register('app.plain', stdClass::class);

        (new ServiceConfigurationCompilerPass())->process($container);

        $this->assertSame([], $missing->getMethodCalls());
        $this->assertSame([], $plain->getMethodCalls());
    }

    public function testDoesNotConfigureAServiceWithoutSupportedTags(): void
    {
        $container = new ContainerBuilder();
        $definition = $container->register('app.untagged', ExamplePackage::class);

        (new ServiceConfigurationCompilerPass())->process($container);

        $this->assertSame([], $definition->getMethodCalls());
    }

    /**
     * @return Generator<string, array{bool|float|int|string|null, list<string>}>
     */
    public static function invalidSections(): Generator
    {
        $paths = [
            [],
            ['components'],
            ['components', 'example'],
            ['components', 'example', 'workers'],
            ['components', 'example', 'workers', 'example'],
        ];
        foreach ($paths as $keys) {
            foreach ([false, 7, 1.5, '', 'invalid', null] as $value) {
                if ($keys !== [] && $value === null) {
                    continue;
                }
                yield implode('.', $keys) . ':' . get_debug_type($value) . ':' . (string) $value => [$value, $keys];
            }
        }
    }

    /**
     * @param bool|float|int|string|null $invalid
     * @param list<string> $keys
     */
    #[DataProvider('invalidSections')]
    public function testRejectsInvalidSectionsWithATranslatablePathAndType(
        mixed $invalid,
        array $keys
    ): void {
        $configuration = $invalid;
        foreach (array_reverse($keys) as $key) {
            $configuration = [$key => $configuration];
        }
        $prefix = 'libredte.lib.';
        $path = $prefix . 'example';
        if ($keys !== []) {
            $path .= '.' . implode('.', $keys);
        }
        $container = new ContainerBuilder();
        $container->setParameter($prefix . 'example', $configuration);
        $definition = $container->register('app.worker', ExampleWorker::class);
        (new ServiceProcessingCompilerPass($prefix))->process($container);

        try {
            (new ServiceConfigurationCompilerPass($prefix))->process($container);
            $this->fail('Invalid configuration must be rejected.');
        } catch (ServiceConfigurationException $exception) {
            $type = get_debug_type($invalid);
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertSame(
                'The configuration at ' . $path . ' must be an array; ' . $type . ' given.',
                $exception->getMessage()
            );
            $this->assertSame([
                'path' => $path,
                'type' => $type,
            ], $exception->getTranslatableMessage()->getParameters());

            $translator = new Translator('es');
            $translator->addLoader('php', new PhpFileLoader());
            $translator->addResource(
                'php',
                dirname(__DIR__, 2) . '/resources/translations/errors+intl-icu.es.php',
                'es',
                'errors+intl-icu'
            );
            $this->assertSame(
                'La configuración en ' . $path . ' debe ser un array; se recibió ' . $type . '.',
                $exception->trans($translator)
            );
        }

        $this->assertSame([], $definition->getMethodCalls());
    }

    /**
     * @return Generator<string, array{array<array-key, mixed>}>
     */
    public static function emptySections(): Generator
    {
        foreach ([
            ['components'],
            ['components', 'example'],
            ['components', 'example', 'workers'],
            ['components', 'example', 'workers', 'example'],
        ] as $keys) {
            foreach (['missing', 'null', 'empty'] as $kind) {
                $configuration = [];
                foreach (array_reverse($keys) as $index => $key) {
                    if ($index === 0) {
                        if ($kind === 'missing') {
                            continue;
                        }
                        $configuration = [$key => $kind === 'null' ? null : []];
                    } else {
                        $configuration = [$key => $configuration];
                    }
                }
                yield implode('.', $keys) . ':' . $kind => [$configuration];
            }
        }
    }

    /**
     * @param array<array-key, mixed> $configuration
     */
    #[DataProvider('emptySections')]
    public function testPreservesMissingNullAndEmptyNestedSections(array $configuration): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('example', $configuration);
        $definition = $container->register('app.worker', ExampleWorker::class);
        (new ServiceProcessingCompilerPass())->process($container);

        (new ServiceConfigurationCompilerPass())->process($container);

        $this->assertSame([], $definition->getMethodCalls());
    }
}
