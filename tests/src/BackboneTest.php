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

use Derafu\ExamplesBackbone\Kernel;
use Derafu\ExamplesBackbone\PackageRegistry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end smoke test of the whole Kernel -> PackageRegistry -> Package ->
 * Component -> Worker -> Job wiring. It necessarily exercises
 * PackageRegistryTrait, which cannot be declared as a coverage target
 * because PHPUnit only accepts classes/interfaces/enums (not traits) and the
 * trait has no real (non-test) consumer in src/ to point at instead. Hence
 * #[CoversNothing]: this is an integration test, not a unit test of one
 * class.
 */
#[CoversNothing]
class BackboneTest extends TestCase
{
    private PackageRegistry $packageRegistry;

    protected function setUp(): void
    {
        $kernel = new Kernel('dev');
        $this->packageRegistry = $kernel->getPackageRegistry();
    }

    public function testExample(): void
    {
        $job = $this
            ->packageRegistry
            ->getExamplePackage()
            ->getExampleComponent()
            ->getExampleWorker()
            ->getExampleJob()
        ;

        $result = $job->execute();

        $this->assertSame('Hello, world!', $result);
    }
}
