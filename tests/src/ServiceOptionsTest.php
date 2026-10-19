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

use ArrayObject;
use Derafu\Backbone\Abstract\AbstractService;
use Derafu\Backbone\Exception\ServiceConfigurationException;
use Derafu\Config\Configuration;
use Derafu\Config\Contract\ConfigurableInterface;
use Derafu\Config\Contract\ConfigurationInterface;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Trait\ConfigurableTrait;
use Derafu\Container\Contract\VaultInterface;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Service options combine configured defaults with explicit overrides.
 */
#[CoversClass(AbstractService::class)]
#[UsesClass(ServiceConfigurationException::class)]
final class ServiceOptionsTest extends TestCase
{
    /**
     * @return Generator<string, array{array<array-key, mixed>|ArrayObject<array-key, mixed>|VaultInterface}>
     */
    public static function argumentContainers(): Generator
    {
        $options = ['transport' => ['port' => 587, 'tls' => false]];
        yield 'array' => [$options];
        $arrayObject = new ArrayObject();
        $arrayObject->exchangeArray($options);
        yield 'array object' => [$arrayObject];
        yield 'vault' => [new Configuration($options)];
    }

    /**
     * @param array<array-key, mixed>|ArrayObject<array-key, mixed>|VaultInterface $options
     */
    #[DataProvider('argumentContainers')]
    public function testSupportsAllArgumentContainersWithoutConfiguration(
        array|ArrayObject|VaultInterface $options
    ): void {
        $result = (new OptionsTestService())->resolveOptions($options);

        $this->assertInstanceOf(OptionsInterface::class, $result);
        $this->assertSame(['transport' => ['port' => 587, 'tls' => false]], $result->all());
    }

    /**
     * @param array<array-key, mixed>|ArrayObject<array-key, mixed>|VaultInterface $options
     */
    #[DataProvider('argumentContainers')]
    public function testArgumentsOverrideConfiguredValuesRecursivelyWithoutMutatingInputs(
        array|ArrayObject|VaultInterface $options
    ): void {
        $defaults = [
            'transport' => ['host' => 'localhost', 'port' => 25, 'tls' => true],
            'retries' => 3,
        ];
        $configuration = new Configuration(['options' => $defaults]);
        $service = new ConfigurableOptionsTestService();
        $service->setConfiguration($configuration);

        $result = $service->resolveOptions($options);

        $this->assertSame([
            'transport' => ['host' => 'localhost', 'port' => 587, 'tls' => false],
            'retries' => 3,
        ], $result->all());
        $this->assertSame(['options' => $defaults], $configuration->all());
        $original = $options instanceof VaultInterface ? $options->all()
            : ($options instanceof ArrayObject ? $options->getArrayCopy() : $options);
        $this->assertSame(['transport' => ['port' => 587, 'tls' => false]], $original);
    }

    /**
     * @return array<string, array{array<array-key, mixed>}>
     */
    public static function absentOrEmptyOptions(): array
    {
        return [
            'missing' => [[]],
            'null' => [['options' => null]],
            'empty array' => [['options' => []]],
        ];
    }

    /**
     * @param array<array-key, mixed> $configuration
     */
    #[DataProvider('absentOrEmptyOptions')]
    public function testAbsentNullAndEmptyOptionsPreserveExplicitArguments(array $configuration): void
    {
        $service = new ConfigurableOptionsTestService();
        $service->setConfiguration($configuration);

        $this->assertSame(['timeout' => 10], $service->resolveOptions(['timeout' => 10])->all());
    }

    public function testDefaultArgumentsUseConfiguredOptions(): void
    {
        $service = new ConfigurableOptionsTestService();
        $service->setConfiguration(['options' => ['timeout' => 30]]);

        $this->assertSame(['timeout' => 30], $service->resolveOptions()->all());
    }

    /**
     * @return array<string, array{array<array-key, mixed>}>
     */
    public static function rawConfiguredArrays(): array
    {
        return [
            'empty' => [[]],
            'populated' => [['timeout' => 30, 'enabled' => false]],
        ];
    }

    /**
     * @param array<array-key, mixed> $defaults
     */
    #[DataProvider('rawConfiguredArrays')]
    public function testAcceptsArraysReturnedByOtherConfigurationImplementations(array $defaults): void
    {
        $configuration = $this->createMock(ConfigurationInterface::class);
        $configuration->expects($this->once())->method('validate');
        $configuration->expects($this->once())->method('get')->with('options')->willReturn($defaults);
        $service = new ConfigurableOptionsTestService();
        $service->setConfiguration($configuration);

        $this->assertSame($defaults, $service->resolveOptions()->all());
    }

    public function testResolvedOptionsStillApplyTheSchema(): void
    {
        $service = new ConfigurableOptionsTestService([
            'timeout' => ['types' => 'int', 'default' => 60],
        ]);
        $service->setConfiguration(['options' => []]);

        $this->assertSame(['timeout' => 60], $service->resolveOptions()->all());
        $this->assertSame(['timeout' => 10], $service->resolveOptions(['timeout' => 10])->all());
    }

    public function testResolvedOptionsStillRejectValuesDisallowedByTheSchema(): void
    {
        $service = new ConfigurableOptionsTestService([
            'timeout' => ['types' => 'int'],
        ]);
        $service->setConfiguration(['options' => ['timeout' => 'invalid']]);

        $this->expectException(InvalidOptionsException::class);
        $service->resolveOptions();
    }

    /**
     * @return Generator<string, array{mixed}>
     */
    public static function invalidConfiguredOptions(): Generator
    {
        foreach ([false, true, 0, 0.0, '', '0', 'invalid', new stdClass(), new ArrayObject()] as $index => $value) {
            yield get_debug_type($value) . ':' . $index => [$value];
        }
    }

    #[DataProvider('invalidConfiguredOptions')]
    public function testInvalidConfiguredOptionsAreRejectedWithATranslatableError(mixed $value): void
    {
        $configuration = $this->createMock(ConfigurationInterface::class);
        $configuration->expects($this->once())->method('validate');
        $configuration->expects($this->once())->method('get')->with('options')->willReturn($value);
        $service = new ConfigurableOptionsTestService();
        $service->setConfiguration($configuration);

        try {
            $service->resolveOptions(['timeout' => 10]);
            $this->fail('Invalid configured options must be rejected.');
        } catch (ServiceConfigurationException $exception) {
            $type = get_debug_type($value);
            $this->assertSame(
                'The configuration at options must be an array or implement ConfigurationInterface; ' . $type . ' given.',
                $exception->getMessage()
            );
            $this->assertSame(['path' => 'options', 'type' => $type], $exception->getTranslatableMessage()->getParameters());
            $translator = new Translator('es');
            $translator->addLoader('php', new PhpFileLoader());
            $translator->addResource(
                'php',
                dirname(__DIR__, 2) . '/resources/translations/errors+intl-icu.es.php',
                'es',
                'errors+intl-icu'
            );
            $this->assertSame(
                'La configuración en options debe ser un array o implementar ConfigurationInterface; se recibió ' . $type . '.',
                $exception->trans($translator)
            );
        }
    }
}

/**
 * A service with a controllable options schema.
 */
class OptionsTestService extends AbstractService
{
    /**
     * @param array<string, array<string, mixed>> $schema
     */
    public function __construct(private array $schema = [])
    {
    }

    public function getOptionsSchema(): array
    {
        return $this->schema;
    }
}

/**
 * The same service with configuration support.
 */
final class ConfigurableOptionsTestService extends OptionsTestService implements ConfigurableInterface
{
    use ConfigurableTrait;
}
