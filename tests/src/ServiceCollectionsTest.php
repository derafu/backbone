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

use ArrayIterator;
use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Backbone\Exception\HandlerException;
use Derafu\Backbone\Exception\JobException;
use Derafu\Backbone\Exception\StrategyNotFoundException;
use Derafu\Backbone\Trait\HandlersAwareTrait;
use Derafu\Backbone\Trait\JobsAwareTrait;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversTrait(HandlersAwareTrait::class)]
#[CoversTrait(JobsAwareTrait::class)]
#[CoversTrait(StrategiesAwareTrait::class)]
#[UsesClass(HandlerException::class)]
#[UsesClass(JobException::class)]
#[UsesClass(StrategyNotFoundException::class)]
final class ServiceCollectionsTest extends TestCase
{
    public function testHandlersPreserveKeysAndIdentityAndReplaceThePreviousCollection(): void
    {
        $service = new CollectionService();
        $mail = $this->createStub(HandlerInterface::class);
        $sms = $this->createStub(HandlerInterface::class);
        $this->assertSame([], $service->getHandlers());
        $this->assertSame($service, $service->setHandlers(['mail' => $mail]));
        $this->assertSame($mail, $service->getHandler('mail'));
        $this->assertSame(['mail' => $mail], $service->getHandlers());

        $this->assertSame($service, $service->setHandlers(new ArrayIterator(['sms' => $sms, 7 => $mail])));
        $this->assertSame(['sms' => $sms, 7 => $mail], $service->getHandlers());
        $this->assertSame($sms, $service->getHandler('sms'));
        $this->assertSame($mail, $service->getHandler('7'));

        $this->expectException(HandlerException::class);
        $this->expectExceptionMessage('Handler mail not found in service Notifications (1000).');
        $service->getHandler('mail');
    }

    public function testJobsPreserveKeysAndIdentityAndReplaceThePreviousCollection(): void
    {
        $service = new CollectionService();
        $build = $this->createStub(JobInterface::class);
        $send = $this->createStub(JobInterface::class);
        $this->assertSame([], $service->getJobs());
        $this->assertSame($service, $service->setJobs(['build' => $build]));
        $this->assertSame($build, $service->getJob('build'));
        $this->assertSame(['build' => $build], $service->getJobs());

        $this->assertSame($service, $service->setJobs(new ArrayIterator(['send' => $send])));
        $this->assertSame(['send' => $send], $service->getJobs());
        $this->assertSame($send, $service->getJob('send'));

        $this->expectException(JobException::class);
        $this->expectExceptionMessage('Job build not found in service Notifications (1000).');
        $service->getJob('build');
    }

    public function testStrategiesPreferAnExactMatchAndOnlyFallbackForUnqualifiedNames(): void
    {
        $service = new CollectionService();
        $exact = $this->createStub(StrategyInterface::class);
        $fallback = $this->createStub(StrategyInterface::class);
        $this->assertSame([], $service->getStrategies());
        $this->assertSame($service, $service->setStrategies(['xml' => $exact, 'default.xml' => $fallback]));
        $this->assertSame($exact, $service->getStrategy('xml'));
        $this->assertSame($fallback, $service->getStrategy('default.xml'));
        $this->assertSame(['xml' => $exact, 'default.xml' => $fallback], $service->getStrategies());

        $this->assertSame($service, $service->setStrategies(new ArrayIterator([
            'default.xml' => $fallback,
            'default.tenant.xml' => $exact,
        ])));
        $this->assertSame($fallback, $service->getStrategy('xml'));
        $this->assertSame([
            'default.xml' => $fallback,
            'default.tenant.xml' => $exact,
        ], $service->getStrategies());

        $this->expectException(StrategyNotFoundException::class);
        $this->expectExceptionMessage('Strategy tenant.xml not found in service Notifications (1000).');
        $service->getStrategy('tenant.xml');
    }

    public function testEmptyIteratorsClearAllCollections(): void
    {
        $service = new CollectionService();
        $service->setHandlers(['mail' => $this->createStub(HandlerInterface::class)]);
        $service->setJobs(['send' => $this->createStub(JobInterface::class)]);
        $service->setStrategies(['xml' => $this->createStub(StrategyInterface::class)]);

        $service->setHandlers(new ArrayIterator());
        $service->setJobs(new ArrayIterator());
        $service->setStrategies(new ArrayIterator());

        $this->assertSame([], $service->getHandlers());
        $this->assertSame([], $service->getJobs());
        $this->assertSame([], $service->getStrategies());
    }
}

final class CollectionService
{
    use HandlersAwareTrait;
    use JobsAwareTrait;
    use StrategiesAwareTrait;

    public function getName(): string
    {
        return 'Notifications';
    }

    public function getId(): int
    {
        return 1000;
    }
}
