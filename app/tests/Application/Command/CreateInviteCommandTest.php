<?php declare(strict_types=1);

namespace App\Tests\Application\Command;

use App\Application\Command\CreateInvite\CreateInviteCommand;
use App\Application\Command\CreateInvite\CreateInviteCommandHandler;
use App\Domain\Events\InviteCreated;
use App\Domain\Model\Invite\InviteRepository;
use App\Domain\Model\Invite\InviteType;
use App\Tests\Doubles\DomainEventBusSpy;

final class CreateInviteCommandTest extends CommandTestCase
{
    private CreateInviteCommandHandler $handler;
    private InviteRepository $repository;
    private DomainEventBusSpy $eventBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new CreateInviteCommandHandler(
            $this->repository = new InviteRepository($this->eventStore),
            $this->eventBus = new DomainEventBusSpy()
        );
    }

    public function test_should_create_invite(): void
    {
        $command = new CreateInviteCommand(
            'day',
            [
                ['type' => 'adult', 'name' => 'Adult'],
                ['type' => 'child', 'name' => 'Child'],
                ['type' => 'baby', 'name' => 'Baby'],
            ]
        );

        ($this->handler)($command);

        $invite = $this->repository->get($command->getId());

        self::assertNotNull($invite->getInviteCode());
        self::assertTrue(InviteType::Day->equals($invite->getInviteType()));
        self::assertCount(3, $invite->getInvitedGuests());
    }

    public function test_it_publishes_invite_created_domain_event_to_bus(): void
    {
        $command = new CreateInviteCommand(
            'day',
            [
                ['type' => 'adult', 'name' => 'Adult'],
                ['type' => 'child', 'name' => 'Child'],
                ['type' => 'baby', 'name' => 'Baby'],
            ]
        );

        ($this->handler)($command);

        self::assertEquals(
            new InviteCreated($command->getId()->toString()),
            $this->eventBus->getLastEvent()
        );
    }

    public function test_fails_to_create_invite_with_invalid_type(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Invalid invite type 'INVALID' supplied");

        new CreateInviteCommand(
            'INVALID',
            [['type' => 'adult', 'name' => 'Adult']]
        );
    }

    public function test_fails_to_create_invite_with_invalid_guest_type(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Invalid guest type 'INVALID' supplied");

        $command = new CreateInviteCommand(
            'day',
            [['type' => 'INVALID', 'name' => 'Adult']]
        );

        ($this->handler)($command);
    }

    public function test_fails_to_create_invite_with_empty_guest_name(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Guests must have a name');

        $command = new CreateInviteCommand(
            'day',
            [['type' => 'adult', 'name' => '']]
        );

        ($this->handler)($command);
    }

    public function test_fails_to_create_invite_with_no_guests(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('An invite must have at least one guest');

        $command = new CreateInviteCommand('day', []);

        ($this->handler)($command);
    }
}
