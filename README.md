# Our Wedding Website

Because every Wedding RSVP website needs to follow DDD, CQRS, Hexagonal Architecture, Event Sourcing, and be deployed on Lambda.

🌎 Website | 📷 [Gallery](https://github.com/eddmann/our-wedding-gallery) | 🏗️ [Infrastructure](https://github.com/eddmann/our-wedding-infra)

## Overview

This application (and [associated infrastructure](https://github.com/eddmann/our-wedding-infra)) documents an approach to building complex systems which require the benefits that DDD, Hexagonal Architecture and Event Sourcing provide.
On-top of this it shows how such an application can be combined with Terraform and deployed in a Serverless manor.

Using PHP and the Symfony framework it highlights how such an approach can be laid out, coupled with a sufficient testing strategy and local development environment.
Some topics and features covered within this application are:

- Use of PHP 8.1 and [Bref](https://bref.sh/) for Serverless Lambda environment.
- Docker-based local [development environment](./docker), which replicates the intended Lambda platform.
- GNU make used to assist in running the application locally and performing CI-based tasks.
- CI pipeline developed using [GitHub workflows](./.github/workflows), running the provided tests and deploying the application to the given stage-environments (staging and production).
- Implements the desired Message buses using [Symfony Messenger](https://symfony.com/doc/current/messenger.html), with asynchronous transport being handled by SQS/Lambda.
- Runtime secrets pulled in via Secrets Manager (and cached using APCu) using a Symfony [environment variable processor](https://symfony.com/doc/current/configuration/env_var_processors.html).
- Email communication sent using [Symfony Mailer](https://symfony.com/doc/current/mailer.html) (via Gmail), with local testing achieved using MailHog.
- [Webpack Encore](https://symfony.com/doc/current/frontend.html) used to transpile and bundle assets (TypeScript, CSS and Images) used throughout the website.
- Event stream snapshots generated and validated within _Application_ level tests, providing regression testing for present stream structures.
- Automated aggregate event diagrams created using the _Application_ level Event stream snapshots, combined with Graphviz.
- Automated documentation/diagrams generated for the present Commands within the system.
- Use of [Deptrac](https://github.com/qossmic/deptrac) to ensure that desired Hexagonal Architecture layering is maintained.
- [Psalm](https://psalm.dev/) and [PHP Coding Standards Fixer](https://cs.symfony.com/) employed to ensure correctness and coding standards maintained.
- DynamoDB configured to manage client sessions within a deployed stage-environment.

## Getting Started

**Prerequisite:** ensure you have Docker installed on your local machine.

```
make start
make open-web
make can-release
```

All available actions within the local development environment are available (and documented) within the [Makefile](Makefile) by running `make help`.

## Architecture

The application follows CQRS for interaction between the _Ui_ and _Application_, Hexagonal Architecture to decouple the _Infrastructural_ concerns, and DDD/Event Sourcing to model the _Domain_.

### Layers

Following Hexagonal Architecture, the layers have been defined like so:

<img src="documentation/hexagonal-architecture.png" width="300">

### Communication Flow

Based on the above layers, we employ three distinct message buses (_Command_, _Aggregate Event_ and _Domain Event_), modeling the Aggregates using Event Sourcing.
The following diagram highlights how these three buses interact during a typical Command/Query lifecycle response.

![](documentation/cqrs-event-sourcing-flow-diagram.png)

### Aggregate Events

There are two Aggregates within the Domain ([FoodChoice](app/src/Domain/Model/FoodChoice) and [Invite](app/src/Domain/Model/Invite)), the Aggregate Event flow for both goes as follows:

![](documentation/aggregate-event-digram.svg)

This diagram is automatically generated based on the current implementation, using [testable](app/tests/Application/Command/CommandTestCase.php) Event [snapshots](app/tests/Application/Command/EventStoreSnapshots) at the Command level.

### Commands and Domain Events

Application-level Commands which are available for the _Ui_ to interact with the _Domain_ are presented below:

![](documentation/command-digram.svg)

Along with the Command and Command Handlers, this also deptics the associated Domain Events which are emitted.

## Testing

The testing strategy employed within this application helps aid in following a [Test Pyramid](https://martinfowler.com/articles/practical-test-pyramid.html), favouring _testing behaviour over implementation_.
In doing this we exercise most of our behavioral assertions at the _Application_ layer, testing the public API provided by the Commands and Query services.
This provides us with a clear description of the intended application's behaviour, whilst reducing the brittleness of the given tests as only public contracts are used.

Testing has been broken up into a similar Hexagonal Architecture layered representation as the system itself, like so:

**Domain**

Low-level domain testing which is heavily coupled to the current implementation.
This is used in cases where you wish to have a higher-level of confidence of a given implementation which can not be easier asserted at the _Application_ level.

**Application**

Unit tests (ala [Unit of behaviour](https://vimeo.com/68375232)) which use the public API exposed by the Commands and Query services to assert correctness.
These are isolated from any infrastructural concerns (via test doubles) and exercise the core business logic/behaviour that the application provides.
This level provides us with the greatest balance between asserting that the current implementation achieves the desired behaviour, whilst not being over-coupled to the implementation causing the test to become brittle.
Depending only on the public API within these tests allows us to refactor the underlying _Domain_ implementation going forward whilst keeping the tests intact.
As such, it is desired to have the most amount of testing at this level.

**Infrastructure**

Contractual tests to assert that the given _adaptor_ implementation completes the required _ports_ responsibility; communicating with external infrastructure (such as a database) in isolation to achieve this.

**Ui**

Full-system tests which exercises the entire system by-way of common use-cases.
This provides us with confidence at the highest-level that the application is achieving the desired behaviour.

## Linting

The application uses the following linting tools to maintain the desired code quality and application correctness.

- [Psalm](https://psalm.dev/) - used to provide type-checking support within PHP (`app/psalm.xml`).
- [PHP Coding Standards Fixer](https://cs.symfony.com/) - ensures the desired PHP code styling is maintained (`app/.php-cs-fixer.php`).
- [Deptrac](https://github.com/qossmic/deptrac) - ensures we adhere to the strict Hexagonal Architectural layering boundaries we have imposed (`depfile.yml`).
- [Local PHP Security Checker](https://github.com/fabpot/local-php-security-checker) - ensures that no known vulnerable dependencies are used within the application.
- [Prettier](https://prettier.io/) - ensures the desired JS code style is maintained (`app/package.json`).

These tools can be run locally using `make lint`, returning a non-zero status code upon failure.
This process is also completed during a `make can-release` invocation.

## Infrastructure

The application is hosted on AWS Lambda with transient infrastructure (which change based on each deployment) being provisioned using the [Serverless Framework](./app/serverless.yml).
Resources managed at this level include Lambda functions, API-gateways and SQS event integrations.
Foundational infrastructural concerns (such as networking, databases, queues etc.) are provisioned using Terraform and can be found in the [related repository](https://github.com/eddmann/our-wedding-infra).

Sharing between Terraform and Serverless Framework is unidirectional, with the application resources that Serverless Framework creates being built upon the _foundation_ that Terraform resources provision.
Parameters, secrets and shared resources which are controlled by Terraform are accessible to this application via SSM parameters and Secrets Manager secrets; providing clear responsibility separation.

### Backends

The application consists of an _Event Store_ which persist aggregate events produced by invoked _Commands_.
It also includes _Projections_ which persist _materialised views_ of these aggregate events, accessible via _Query_ services.
These two responsibilities do not require a shared data-store, and can manage their own state as best they see fit.

To highlight this, I have built several persistence implementations of both the Event Store and Projections.
This exercises the importance of good abstraction, and the benefits of layering your application using Hexagonal Architecture (Ports and Adaptors).
Although only one is used within the production setting (Postgres), as a local demonstration it is interesting to see the differences.

#### Event Store

Using the provided [Event Store](app/src/Domain/Helpers/EventStore.php) _port_ interface, there are the following implementations:

- Postgres

  - Stores the events in sequential order within a single-table.
  - Ensures domain aggregate invariance, using version constraints to prevent competing client updates.
  - Uses a transaction to ensure that all aggregate events are persisted to the event stream in one atomic operation.
  - Using a single-table design (with a sequential ordering) allows for trivial event store streaming capabilities.

- DynamoDB

  - Stores the events within a single-table, with the aggregate identifier and version mapping well to DynamoDB's partition and sort keys respectively.
  - Ensures domain aggregate invariance, using DynamoDB write constraints to prevent competing client updates.
  - The aggregate events are currently not persisted within a single write transaction, as the underlying client (AsyncAWS) does not support this yet.
  - The sequential ordering is carried out using the event creation time within micro-seconds.
    Due to DynamoDB's behavioral properties, you are unable to maintain a sequential auto-incrementing identifier.
    Although not ideal, this is the best we can attain from using such a means.
  - Currently, a _hot partition key_ is used based on this event creation time to provide the same sequential ordering required for streaming.

Future developments that I wish to consider, are to instead store the events within S3 for persistence (possibly via DynamoDB streams for durability), and then query for these using Athena when streaming operations are required.
In doing this we would move away from the _hot key_ that has been introduced in its current form.

- EventStoreDB

  - Communicates with the EventStoreDB over HTTP via Atom.
  - Separate event streams are created per aggregate, following the naming convention _#AGGREGATE_NAME#-#AGGREGATE_ID#_.
  - Ensures domain aggregate invariance, using the event streams expected version constraints which are provided by the persistence layer.

With the HTTP and Atom communication protocols being deprecated, work to replace this with a gRPC client written in PHP would need to be carried out before putting such an implementation in production.

#### Projections
