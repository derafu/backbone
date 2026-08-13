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

use Derafu\Backbone\Exception\ComponentNotFoundException;
use Derafu\Backbone\Exception\HandlerNotFoundException;
use Derafu\Backbone\Exception\JobNotFoundException;
use Derafu\Backbone\Exception\PackageNotFoundException;
use Derafu\Backbone\Exception\ServiceNotFoundException;
use Derafu\Backbone\Exception\StrategyNotFoundException;
use Derafu\Backbone\Exception\WorkerNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for two bugs found while testing derafu-libredte-phpy's
 * backbone-dispatcher/backbone-api against real usage:
 *
 *  1. `ServiceNotFoundException::forService()` used `new self(...)` instead
 *     of `new static(...)`, so calling it through `parent::forService()`
 *     from a subclass factory (e.g. `ComponentNotFoundException::forComponent()`)
 *     always returned a plain `ServiceNotFoundException`, never the
 *     specific subclass. `catch (ComponentNotFoundException $e)` never
 *     actually worked.
 *  2. The translatable message array was nested (`['template', ['key' =>
 *     value]]`) instead of flat (`['template', 'key' => value]`), so
 *     `TranslatableMessage`'s ICU placeholders were never substituted and
 *     `getMessage()` returned the raw, unfilled template.
 */
#[CoversClass(ServiceNotFoundException::class)]
#[CoversClass(PackageNotFoundException::class)]
#[CoversClass(ComponentNotFoundException::class)]
#[CoversClass(WorkerNotFoundException::class)]
#[CoversClass(JobNotFoundException::class)]
#[CoversClass(HandlerNotFoundException::class)]
#[CoversClass(StrategyNotFoundException::class)]
class ServiceNotFoundExceptionTest extends TestCase
{
    public function testForPackageReturnsThePackageSubclassWithASubstitutedMessage(): void
    {
        $e = PackageNotFoundException::forPackage('billing');

        $this->assertInstanceOf(PackageNotFoundException::class, $e);
        $this->assertSame(
            'The package billing does not exist in the application.',
            $e->getMessage()
        );
    }

    public function testForComponentReturnsTheComponentSubclassWithASubstitutedMessage(): void
    {
        $e = ComponentNotFoundException::forComponent('document');

        $this->assertInstanceOf(ComponentNotFoundException::class, $e);
        $this->assertSame(
            'The component document does not exist in the application.',
            $e->getMessage()
        );
    }

    public function testForWorkerReturnsTheWorkerSubclassWithASubstitutedMessage(): void
    {
        $e = WorkerNotFoundException::forWorker('builder');

        $this->assertInstanceOf(WorkerNotFoundException::class, $e);
        $this->assertSame(
            'The worker builder does not exist in the application.',
            $e->getMessage()
        );
    }

    public function testForJobReturnsTheJobSubclassWithASubstitutedMessage(): void
    {
        $e = JobNotFoundException::forJob('build');

        $this->assertInstanceOf(JobNotFoundException::class, $e);
        $this->assertSame(
            'The job build does not exist in the application.',
            $e->getMessage()
        );
    }

    public function testForHandlerReturnsTheHandlerSubclassWithASubstitutedMessage(): void
    {
        $e = HandlerNotFoundException::forHandler('normalizer');

        $this->assertInstanceOf(HandlerNotFoundException::class, $e);
        $this->assertSame(
            'The handler normalizer does not exist in the application.',
            $e->getMessage()
        );
    }

    public function testForStrategyReturnsTheStrategySubclassWithASubstitutedMessage(): void
    {
        $e = StrategyNotFoundException::forStrategy('xml');

        $this->assertInstanceOf(StrategyNotFoundException::class, $e);
        $this->assertSame(
            'The strategy xml does not exist in the application.',
            $e->getMessage()
        );
    }
}
