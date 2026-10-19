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

use Derafu\Backbone\Contract\PackageInterface;
use Derafu\Backbone\Contract\PackageProviderInterface;
use Derafu\Backbone\Contract\PackageRegistryInterface;
use Derafu\Backbone\Exception\PackageNotFoundException;
use Derafu\Backbone\Exception\ServiceNotFoundException;
use Derafu\Backbone\Trait\PackageRegistryTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversTrait(PackageRegistryTrait::class)]
#[UsesClass(PackageNotFoundException::class)]
#[UsesClass(ServiceNotFoundException::class)]
final class PackageRegistryTraitTest extends TestCase
{
    public function testInitialPackagesAreAvailableUnderTheirRegisteredNames(): void
    {
        $billing = $this->createStub(PackageInterface::class);
        $mail = $this->createStub(PackageInterface::class);
        $registry = new RegistryForTest(['billing' => $billing, 'mail' => $mail]);

        $this->assertTrue($registry->hasPackage('billing'));
        $this->assertTrue($registry->hasPackage('mail'));
        $this->assertFalse($registry->hasPackage('unknown'));
        $this->assertSame($billing, $registry->getPackage('billing'));
        $this->assertSame($mail, $registry->getPackage('mail'));
        $this->assertSame(['billing' => $billing, 'mail' => $mail], $registry->getPackages());
    }

    public function testProviderPackagesAreLoadedOnceAndCanBeReplaced(): void
    {
        $original = $this->createStub(PackageInterface::class);
        $replacement = $this->createStub(PackageInterface::class);
        $provider = $this->createMock(PackageProviderInterface::class);
        $provider->expects($this->once())->method('getPackages')->willReturn(['billing' => $original]);
        $registry = new RegistryForTest($provider);
        $this->assertSame($original, $registry->getPackage('billing'));

        $this->assertSame($registry, $registry->registerPackage('billing', $replacement));
        $this->assertSame($replacement, $registry->getPackage('billing'));
        $this->assertSame(['billing' => $replacement], $registry->getPackages());
    }

    public function testAnEmptyRegistryCanRegisterAPackage(): void
    {
        $registry = new RegistryForTest([]);
        $this->assertSame([], $registry->getPackages());
        $package = $this->createStub(PackageInterface::class);

        $this->assertSame($registry, $registry->registerPackage('billing', $package));
        $this->assertTrue($registry->hasPackage('billing'));
        $this->assertSame($package, $registry->getPackage('billing'));
    }

    public function testAMissingPackageReportsItsName(): void
    {
        $registry = new RegistryForTest([]);
        $this->expectException(PackageNotFoundException::class);
        $this->expectExceptionMessage('The package billing does not exist in the application.');
        $registry->getPackage('billing');
    }
}

final class RegistryForTest implements PackageRegistryInterface
{
    use PackageRegistryTrait;
}
