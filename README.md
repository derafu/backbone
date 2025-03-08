# Derafu: Backbone - The Architectural Spine for PHP Libraries

![GitHub last commit](https://img.shields.io/github/last-commit/derafu/backbone/main)
![CI Workflow](https://github.com/derafu/backbone/actions/workflows/ci.yml/badge.svg?branch=main&event=push)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/derafu/backbone)
![GitHub Issues](https://img.shields.io/github/issues-raw/derafu/backbone)
![Total Downloads](https://poser.pugx.org/derafu/backbone/downloads)
![Monthly Downloads](https://poser.pugx.org/derafu/backbone/d/monthly)

Derafu Backbone is a lightweight architectural framework that provides a consistent structure for building modular, maintainable PHP libraries.

## Features

- **Hierarchical Organization**: Clear structure with Packages, Components, and Workers.
- **Separation of Concerns**: Jobs for atomic operations, Handlers for orchestration, Strategies for implementation variants.
- **Attribute-Based Discovery**: Use PHP 8 attributes instead of rigid namespace conventions.
- **Extensible Architecture**: Designed to grow with your application.

## Key Benefits

- **Consistent Structure**: Standardized approach to organizing domain logic.
- **Reduced Complexity**: Clear responsibilities for each architectural element.
- **Improved Testability**: Isolated components are easier to test.
- **Enhanced Collaboration**: Common vocabulary and patterns for development teams.
- **Flexible Implementation**: Adapt to different domains without changing the core architecture.

## Installation

```bash
composer require derafu/backbone
```

## Quick Example

```php
#[Package(name: 'billing')]
class BillingPackage extends AbstractPackage implements PackageInterface
{
    // Package implementation.
}

#[Component(name: 'document', package: 'billing')]
class DocumentComponent extends AbstractComponent implements ComponentInterface
{
    // Component implementation.
}

#[Worker(name: 'renderer', component: 'document', package: 'billing')]
class RendererWorker extends AbstractWorker implements WorkerInterface
{
    // Worker implementation.
}
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
