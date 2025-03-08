<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Contract;

/**
 * Interface for the package provider of the application.
 */
interface PackageProviderInterface
{
    /**
     * Returns the list of packages.
     *
     * @return array<string, PackageInterface> The list of packages.
     */
    public function getPackages(): array;
}
