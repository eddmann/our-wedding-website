# Our Wedding

Because every Wedding RSVP website needs to follow DDD, CQRS, Hexagonal Architecture, Event Sourcing, and be deployed on Lambda.

## Getting Started

```
make start
make open-web
make can-release
```

All available actions within the local development environment are available (and documented) within the [Makefile](Makefile) by running `make help`.

## Architecture

The application follows CQRS for interaction between the *Ui* and *Application*, Hexagonal Architecture to decouple the *Infrastructural* concerns, and DDD/Event Sourcing to model the *Domain*.

### Layers

Following Hexagonal Architecture, the layers have been defined like so:

<img src="documentation/hexagonal-architecture.png" width="300">

### Flow

Based on the above layers, we employ three distinct message buses (*Command*, *Aggregate Event* and *Domain Event*), modeling the Aggregates using Event Sourcing.
The following diagram highlights how these three buses interact during a typical Command/Query lifecycle response.

![](documentation/cqrs-event-sourcing-flow-diagram.png)

### Aggregate Events

There are two Aggregates within the Domain ([FoodChoice](app/src/Domain/Model/FoodChoice) and [Invite](app/src/Domain/Model/Invite)), the Aggregate Event flow for both goes as follows:

![](documentation/aggregate-event-digram.svg)

This diagram is automatically generated based on the current implementation, using [testable](app/tests/Application/Command/CommandTestCase.php) Event [snapshots](app/tests/Application/Command/EventStoreSnapshots) at the Command level.

#### Commands and Domain Events

Application-level Commands which are available for the *Ui* to interact with the *Domain* are presented below:

![](documentation/command-digram.svg)

Along with the Command and Command Handlers, this also deptics the associated Domain Events which are emitted.

## Testing

Try and keep testing the behaviour at the Application layer (public inteface which will not change) and out.
Specific domain tests for projections and model concepts implementations if required due to completelixy and value.

## Linting

The application uses the following linting tools to maintain the desired code quality and correctness.

- [Psalm](https://psalm.dev/) - used to provide type-checking support within PHP (`app/psalm.xml`)
- [PHP Coding Standards Fixer](https://cs.symfony.com/) - ensures the desired PHP code styling is maintained (`app/.php-cs-fixer.php`)
- [Deptrac](https://github.com/qossmic/deptrac) - ensures we adhere to the strict Hexagonal Architectural layering boundaries we have imposed (`depfile.yml`)
- [Local PHP Security Checker](https://github.com/fabpot/local-php-security-checker) - ensures that no known vulnerable dependencies are used within the application
- [Prettier](https://prettier.io/) - ensures the desired JS code style is maintained (`app/package.json`)

These tools can be run locally using `make lint`, returning a non-zero status code upon failure.
This process is also completed during a `make can-release` invocation.

## Infrastructure
