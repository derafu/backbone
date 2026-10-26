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

use Derafu\Backbone\Exception\ComponentNotFoundException;
use Derafu\Backbone\Exception\ServiceNotFoundException;
use Derafu\Backbone\Translation\BackboneTranslationResourceProvider;
use Derafu\Translation\TranslatorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Import the provider-only configuration and consume its tagged catalogues.
 */
#[CoversClass(BackboneTranslationResourceProvider::class)]
#[UsesClass(ComponentNotFoundException::class)]
#[UsesClass(ServiceNotFoundException::class)]
final class ServicesTest extends TestCase
{
    private function load(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/resources/config')))
            ->load('backbone-services.yaml');

        return $container;
    }

    public function testTheConfiguredProviderCanBeConstructed(): void
    {
        $container = $this->load();
        $container->getDefinition(BackboneTranslationResourceProvider::class)->setPublic(true);
        $container->compile();
        $provider = $container->get(BackboneTranslationResourceProvider::class);
        $this->assertInstanceOf(BackboneTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        $this->assertCount(1, $directories);
        $this->assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
    }

    public function testTaggedResourcesTranslateThePackagesExceptionWithItsParameters(): void
    {
        $container = $this->load();
        $this->assertArrayHasKey(
            BackboneTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $container->register('app.translator', Translator::class)
            ->setFactory([TranslatorFactory::class, 'create'])
            ->setArguments(['es', ['en'], new TaggedIteratorArgument('derafu_translation.resource_provider')])
            ->setPublic(true);
        $container->compile();
        $translator = $container->get('app.translator');
        $this->assertInstanceOf(Translator::class, $translator);
        $exception = ComponentNotFoundException::forComponent('missing');
        $this->assertSame('The component missing does not exist in the application.', $exception->getMessage());
        $this->assertSame('No existe en la aplicación: component missing.', $exception->trans($translator));
    }
}
