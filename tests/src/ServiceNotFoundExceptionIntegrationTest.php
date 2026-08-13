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
use Derafu\Backbone\Exception\WorkerNotFoundException;
use Derafu\ExamplesBackbone\Kernel;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Proves the ServiceNotFoundException::forService() fix also holds through
 * the real call paths (AbstractPackage::getComponent(),
 * AbstractComponent::getWorker()) via a full Kernel/DI bootstrap, not just
 * when calling the exception factories directly (see
 * ServiceNotFoundExceptionTest). #[CoversNothing]: bootstrapping the Kernel
 * necessarily touches PackageRegistryTrait/JobsAwareTrait, which cannot be
 * declared as coverage targets (PHPUnit does not accept traits), same as
 * BackboneTest.
 */
#[CoversNothing]
class ServiceNotFoundExceptionIntegrationTest extends TestCase
{
    public function testGetComponentThrowsTheSpecificComponentNotFoundException(): void
    {
        $kernel = new Kernel('dev');
        $package = $kernel->getPackageRegistry()->getExamplePackage();

        try {
            $package->getComponent('unknown');
            $this->fail('Expected ComponentNotFoundException was not thrown.');
        } catch (ComponentNotFoundException $e) {
            $this->assertSame(
                'The component unknown does not exist in the application.',
                $e->getMessage()
            );
        }
    }

    public function testGetWorkerThrowsTheSpecificWorkerNotFoundException(): void
    {
        $kernel = new Kernel('dev');
        $component = $kernel->getPackageRegistry()->getExamplePackage()->getExampleComponent();

        try {
            $component->getWorker('unknown');
            $this->fail('Expected WorkerNotFoundException was not thrown.');
        } catch (WorkerNotFoundException $e) {
            $this->assertSame(
                'The worker unknown does not exist in the application.',
                $e->getMessage()
            );
        }
    }
}
