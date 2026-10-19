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

use Attribute;
use Derafu\Backbone\Abstract\AbstractService;
use Derafu\Backbone\Abstract\AbstractServiceMetadata;
use Derafu\Backbone\Attribute\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractService::class)]
#[CoversClass(AbstractServiceMetadata::class)]
#[UsesClass(Package::class)]
final class ServiceIdentityTest extends TestCase
{
    public function testDiscoversMetadataAmongOtherAttributesAndCachesTheInstance(): void
    {
        $before = IdentityMarker::$instances;
        $service = new DescribedIdentityService();

        $this->assertSame('billing', $service->getName());
        $this->assertSame('billing', $service->getId());
        $this->assertSame('Billing package.', $service->getDescription());
        $this->assertSame('Package billing (billing) Billing package.', (string) $service);
        $this->assertSame('billing', $service->getName());
        $this->assertSame($before + 1, IdentityMarker::$instances);
    }

    public function testMissingDescriptionIsNullAndDoesNotLeaveATrailingSpace(): void
    {
        $service = new PlainIdentityService();

        $this->assertNull($service->getDescription());
        $this->assertSame('Package plain (plain)', (string) $service);
    }
}

#[Attribute(Attribute::TARGET_CLASS)]
final class IdentityMarker
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }
}

#[IdentityMarker]
#[Package(name: 'billing', description: 'Billing package.')]
final class DescribedIdentityService extends AbstractService
{
}

#[Package(name: 'plain')]
final class PlainIdentityService extends AbstractService
{
}
