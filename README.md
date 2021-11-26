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

Based on the above layering, the application follows the Command/Query and Event Sourcing flow as follows:

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

## Infrastructure
