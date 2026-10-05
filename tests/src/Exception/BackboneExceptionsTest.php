<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsBackbone\Exception;

use Closure;
use Derafu\Backbone\Abstract\AbstractService;
use Derafu\Backbone\Abstract\AbstractServiceMetadata;
use Derafu\Backbone\Enum\ServiceType;
use Derafu\Backbone\Exception\HandlerException;
use Derafu\Backbone\Exception\JobException;
use Derafu\Backbone\Exception\StrategyException;
use Derafu\Backbone\Trait\HandlersAwareTrait;
use Derafu\Backbone\Trait\JobsAwareTrait;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Translation\Contract\TranslatableInterface;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * The errors of the package are translatable and say what they always did.
 *
 * The traits can not be declared as coverage targets (PHPUnit does not accept
 * traits), so what they do is covered through the classes that use them here.
 */
#[CoversClass(AbstractService::class)]
#[CoversClass(AbstractServiceMetadata::class)]
#[UsesClass(HandlerException::class)]
#[UsesClass(JobException::class)]
#[UsesClass(StrategyException::class)]
#[UsesTrait(HandlersAwareTrait::class)]
#[UsesTrait(JobsAwareTrait::class)]
#[UsesTrait(StrategiesAwareTrait::class)]
final class BackboneExceptionsTest extends TestCase
{
    /**
     * @return array<string, array{Closure(): mixed, class-string<Throwable>, string}>
     */
    public static function failuresProvider(): array
    {
        return [
            'handler that is not in the service' => [
                fn () => (new ServiceWithTraits())->getHandler('mail'),
                HandlerException::class,
                'Handler mail not found in service Fixture (1000).',
            ],
            'strategy that is not in the service' => [
                fn () => (new ServiceWithTraits())->getStrategy('mail'),
                StrategyException::class,
                'Strategy mail not found in service Fixture (1000).',
            ],
            'job that is not in the service' => [
                fn () => (new ServiceWithTraits())->getJob('mail'),
                JobException::class,
                'Job mail not found in service Fixture (1000).',
            ],
            'service without metadata' => [
                fn () => (new ServiceWithoutMetadata())->getName(),
                LogicException::class,
                'The metadata attribute of the service ' . ServiceWithoutMetadata::class . ' is not defined.',
            ],
            'metadata without id' => [
                fn () => (new MetadataWithoutProperties())->getId(),
                LogicException::class,
                'The id property is required',
            ],
            'metadata without name' => [
                fn () => (new MetadataWithoutProperties())->getName(),
                LogicException::class,
                'The name property is required',
            ],
        ];
    }

    /**
     * @param Closure(): mixed $action
     * @param class-string<Throwable> $class
     */
    #[DataProvider('failuresProvider')]
    public function testEveryFailureIsATranslatableErrorThatSaysTheSame(Closure $action, string $class, string $message): void
    {
        $exception = null;
        try {
            $action();
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf($class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame($message, $exception->getMessage());
    }
}

/**
 * Uses the traits that look for things in a service.
 */
final class ServiceWithTraits
{
    use HandlersAwareTrait;
    use JobsAwareTrait;
    use StrategiesAwareTrait;

    public function getName(): string
    {
        return 'Fixture';
    }

    public function getId(): int
    {
        // A number, to be sure that it is shown as it is (without separators).
        return 1000;
    }
}

final class ServiceWithoutMetadata extends AbstractService
{
}

final class MetadataWithoutProperties extends AbstractServiceMetadata
{
    public function getType(): ServiceType
    {
        return ServiceType::JOB;
    }

    public function getParentId(): ?string
    {
        return null;
    }

    public function toArray(): array
    {
        return [];
    }
}
