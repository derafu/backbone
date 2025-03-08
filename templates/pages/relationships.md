# Relationships and Interactions

[TOC]

{.w-50 .mx-auto}
![Derafu Backbone Relationships](/img/derafu-backbone-relationships.svg)

## Package → Component Relationship

- A Package contains multiple Components.
- Components within a Package are thematically related.
- Components access Package-level configuration and services.

## Component → Worker Relationship

- A Component contains multiple Workers.
- Workers are grouped logically within a Component.
- Workers share Component-level resources and configuration.

## Worker → Job/Handler/Strategy Relationship

- Workers expose public methods that can be called by clients.
- Workers delegate to Jobs for simple operations.
- Workers delegate to Handlers for complex workflows.
- Workers don't typically interact directly with Strategies (Handlers do).

## Handler → Job/Strategy Relationship

- Handlers coordinate the execution of multiple Jobs.
- Handlers select and use appropriate Strategies based on context.
- Handlers implement orchestration logic while Jobs and Strategies provide implementation.

## Strategy Relationships

- Strategies implement interchangeable algorithms.
- Handlers select appropriate Strategies based on conditions.
- Strategies focus on specific implementation details.