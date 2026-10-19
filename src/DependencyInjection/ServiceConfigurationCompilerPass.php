<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\DependencyInjection;

use Derafu\Backbone\Exception\ServiceConfigurationException;
use Derafu\Config\Contract\ConfigurableInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class to assign the configurations of services as a method call when the
 * services are created.
 */
final class ServiceConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * Constructor of the compiler pass.
     *
     * @param string $servicesPrefix The prefix for the services in the parameters.
     */
    public function __construct(private readonly string $servicesPrefix = '')
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $params = $container->getParameterBag();

        foreach ($container->getDefinitions() as $id => $definition) {
            // Only process classes that exist and are services.
            $class = $definition->getClass() ?? $id;
            if (
                !class_exists($class)
                || !is_subclass_of($class, ConfigurableInterface::class)
            ) {
                continue;
            }

            // Get the configuration of the service and add a method call to set
            // it when the service is created.
            $config = $this->resolveConfiguration($definition, $params);
            if ($config) {
                $definition->addMethodCall('setConfiguration', [$config]);
            }
        }
    }

    /**
     * Gets the configuration of a definition.
     *
     * @param Definition $definition
     * @param ParameterBagInterface $params
     * @return array<array-key, mixed> Configuration if it exists, otherwise an empty array.
     */
    private function resolveConfiguration(
        Definition $definition,
        ParameterBagInterface $params
    ): array {
        $config = [];
        $tags = $definition->getTags();

        if (!empty($tags['service::package'])) {
            $package = $tags['service::package'][0]['name'];
            $config = $this->getPackageParameter($params, $package);
        } elseif (!empty($tags['service::component'])) {
            $package = $tags['service::component'][0]['package'];
            $component = $tags['service::component'][0]['name'];
            $config = $this->getPackageParameter($params, $package);
            $config = $this->getNestedConfiguration(
                $config,
                ['components', $component],
                $this->servicesPrefix . $package
            );
        } elseif (!empty($tags['service::worker'])) {
            $package = $tags['service::worker'][0]['package'];
            $component = $tags['service::worker'][0]['component'];
            $worker = $tags['service::worker'][0]['name'];
            $config = $this->getPackageParameter($params, $package);
            $config = $this->getNestedConfiguration(
                $config,
                ['components', $component, 'workers', $worker],
                $this->servicesPrefix . $package
            );
        }

        return $config;
    }

    /**
     * Gets the top-level configuration parameter of a package.
     *
     * Not every package necessarily has a matching parameter defined (e.g. a
     * package with no configuration at all), so this must not fail when the
     * parameter is missing.
     *
     * @param ParameterBagInterface $params
     * @param string $package
     * @return array<array-key, mixed>
     */
    private function getPackageParameter(
        ParameterBagInterface $params,
        string $package
    ): array {
        $name = $this->servicesPrefix . $package;

        return $params->has($name)
            ? $this->requireConfigurationArray($params->get($name), $name)
            : [];
    }

    /**
     * Resolves a configuration path, treating missing or null sections as empty.
     *
     * @param array<array-key, mixed> $configuration
     * @param list<string> $keys
     * @param string $path The full parameter path for error reporting.
     * @return array<array-key, mixed>
     * @throws ServiceConfigurationException If a section is not an array.
     */
    private function getNestedConfiguration(
        array $configuration,
        array $keys,
        string $path
    ): array {
        foreach ($keys as $key) {
            $path .= '.' . $key;
            $configuration = $this->requireConfigurationArray(
                $configuration[$key] ?? [],
                $path
            );
        }

        return $configuration;
    }

    /**
     * Requires an array at a configuration path without converting its values.
     *
     * @param mixed $configuration
     * @param string $path
     * @return array<array-key, mixed>
     * @throws ServiceConfigurationException If the value is not an array.
     */
    private function requireConfigurationArray(
        mixed $configuration,
        string $path
    ): array {
        if (!is_array($configuration)) {
            throw new ServiceConfigurationException([
                'The configuration at {path} must be an array; {type} given.',
                'path' => $path,
                'type' => get_debug_type($configuration),
            ]);
        }

        return $configuration;
    }
}
