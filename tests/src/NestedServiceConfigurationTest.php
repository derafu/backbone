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

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Abstract\AbstractPackage;
use Derafu\Backbone\Contract\ComponentInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Backbone\Exception\ComponentNotFoundException;
use Derafu\Backbone\Exception\ServiceConfigurationException;
use Derafu\Backbone\Exception\WorkerNotFoundException;
use Derafu\Config\Configuration;
use Derafu\Config\Contract\ConfigurationInterface;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Package and component configuration readers preserve supported values.
 */
#[CoversClass(AbstractPackage::class)]
#[CoversClass(AbstractComponent::class)]
#[UsesClass(ServiceConfigurationException::class)]
#[UsesClass(ComponentNotFoundException::class)]
#[UsesClass(WorkerNotFoundException::class)]
final class NestedServiceConfigurationTest extends TestCase
{
    /**
     * @return Generator<string, array{string, string}>
     */
    public static function readers(): Generator
    {
        yield 'component configuration' => ['package', 'components.example'];
        yield 'worker configuration' => ['component', 'workers.example'];
    }

    private function createReader(string $kind): PackageConfigurationReader|ComponentConfigurationReader
    {
        return $kind === 'package'
            ? new PackageConfigurationReader()
            : new ComponentConfigurationReader();
    }

    #[DataProvider('readers')]
    public function testMissingConfigurationRemainsEmpty(string $kind, string $path): void
    {
        $reader = $this->createReader($kind);
        $this->assertSame([], $reader->readConfiguration('example'));
    }

    #[DataProvider('readers')]
    public function testNullConfigurationRemainsEmpty(string $kind, string $path): void
    {
        $reader = $this->createReader($kind);
        $configuration = new Configuration();
        $configuration->set($path, null);
        $reader->setConfiguration($configuration);
        $this->assertSame([], $reader->readConfiguration('example'));
    }

    #[DataProvider('readers')]
    public function testReadsArraysThroughTheStandardConfiguration(string $kind, string $path): void
    {
        $reader = $this->createReader($kind);
        $configuration = new Configuration();
        $configuration->set($path, ['enabled' => false, 'timeout' => 30]);
        $reader->setConfiguration($configuration);

        $result = $reader->readConfiguration('example');

        $this->assertInstanceOf(ConfigurationInterface::class, $result);
        $this->assertSame(['enabled' => false, 'timeout' => 30], $result->all());
    }

    #[DataProvider('readers')]
    public function testPreservesArraysReturnedByOtherImplementations(string $kind, string $path): void
    {
        $reader = $this->createReader($kind);
        foreach ([[], ['enabled' => false, 'timeout' => 30]] as $value) {
            $configuration = $this->createMock(ConfigurationInterface::class);
            $configuration->expects($this->never())->method('validate');
            $configuration->expects($this->once())->method('get')->with($path)->willReturn($value);
            $reader->setConfiguration($configuration);

            $this->assertSame($value, $reader->readConfiguration('example'));
        }
    }

    #[DataProvider('readers')]
    public function testPreservesTheExactConfigurationInstance(string $kind, string $path): void
    {
        $reader = $this->createReader($kind);
        $value = new Configuration(['enabled' => false]);
        $configuration = $this->createMock(ConfigurationInterface::class);
        $configuration->expects($this->never())->method('validate');
        $configuration->expects($this->once())->method('get')->with($path)->willReturn($value);
        $reader->setConfiguration($configuration);

        $this->assertSame($value, $reader->readConfiguration('example'));
    }

    /**
     * @return Generator<string, array{string, string, mixed}>
     */
    public static function invalidValues(): Generator
    {
        foreach (self::readers() as $readerName => [$kind, $path]) {
            foreach ([false, true, 0, 7, 1.5, '', 'invalid', new stdClass()] as $index => $value) {
                yield $readerName . ':' . $index => [$kind, $path, $value];
            }
        }
    }

    #[DataProvider('invalidValues')]
    public function testRejectsUnsupportedValuesWithATranslatableError(
        string $kind,
        string $path,
        mixed $value
    ): void {
        $reader = $this->createReader($kind);
        $configuration = $this->createMock(ConfigurationInterface::class);
        $configuration->expects($this->never())->method('validate');
        $configuration->expects($this->once())->method('get')->with($path)->willReturn($value);
        $reader->setConfiguration($configuration);

        try {
            $reader->readConfiguration('example');
            $this->fail('Unsupported configuration must be rejected.');
        } catch (ServiceConfigurationException $exception) {
            $type = get_debug_type($value);
            $this->assertSame(
                'The configuration at ' . $path . ' must be an array or implement ConfigurationInterface; ' . $type . ' given.',
                $exception->getMessage()
            );
            $this->assertSame(['path' => $path, 'type' => $type], $exception->getTranslatableMessage()->getParameters());

            $translator = new Translator('es');
            $translator->addLoader('php', new PhpFileLoader());
            $translator->addResource(
                'php',
                dirname(__DIR__, 2) . '/resources/translations/errors+intl-icu.es.php',
                'es',
                'errors+intl-icu'
            );
            $this->assertSame(
                'La configuración en ' . $path . ' debe ser un array o implementar ConfigurationInterface; se recibió ' . $type . '.',
                $exception->trans($translator)
            );
        }
    }

    public function testPackageReturnsTheRegisteredComponentAndRejectsMissingNames(): void
    {
        $component = $this->createStub(ComponentInterface::class);
        $reader = new PackageConfigurationReader(['example' => $component]);
        $this->assertSame($component, $reader->getComponent('example'));

        $this->expectException(ComponentNotFoundException::class);
        $reader->getComponent('missing');
    }

    public function testComponentReturnsTheRegisteredWorkerAndRejectsMissingNames(): void
    {
        $worker = $this->createStub(WorkerInterface::class);
        $reader = new ComponentConfigurationReader(['example' => $worker]);
        $this->assertSame($worker, $reader->getWorker('example'));

        $this->expectException(WorkerNotFoundException::class);
        $reader->getWorker('missing');
    }
}

/**
 * Exposes the protected package configuration reader for testing.
 */
final class PackageConfigurationReader extends AbstractPackage
{
    /**
     * @param array<string, ComponentInterface> $components
     */
    public function __construct(private array $components = [])
    {
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @return array<array-key, mixed>|ConfigurationInterface
     */
    public function readConfiguration(string $name): array|ConfigurationInterface
    {
        return $this->getComponentConfiguration($name);
    }
}

/**
 * Exposes the protected component configuration reader for testing.
 */
final class ComponentConfigurationReader extends AbstractComponent
{
    /**
     * @param array<string, WorkerInterface> $workers
     */
    public function __construct(private array $workers = [])
    {
    }

    public function getWorkers(): array
    {
        return $this->workers;
    }

    /**
     * @return array<array-key, mixed>|ConfigurationInterface
     */
    public function readConfiguration(string $name): array|ConfigurationInterface
    {
        return $this->getWorkerConfiguration($name);
    }
}
