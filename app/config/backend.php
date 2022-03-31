<?php declare(strict_types=1);

use App\Application\Command\AuthenticateInvite\InviteCodeToIdFinder;
use App\Domain\Helpers\EventStore;
use App\Domain\Model\Invite\Guest\ChosenFoodChoiceValidator;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use App\Domain\Projection\SentInvite\SentInviteRepository;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $configurator->import('backend/postgres.yaml');
    $configurator->import('backend/dynamodb.yaml');
    $configurator->import('backend/eventstoredb.yaml');

    $eventStoreBackend = \getenv('EVENT_STORE_BACKEND') ?: 'Postgres';
    $projectionBackend = \getenv('PROJECTION_BACKEND') ?: 'Postgres';

    $configurator->services()->alias(EventStore::class, "App\\Infrastructure\\{$eventStoreBackend}\\{$eventStoreBackend}EventStore");
    $configurator->services()->alias(SubmittedSongChoiceRepository::class, "App\\Infrastructure\\{$projectionBackend}\\{$projectionBackend}SubmittedSongChoiceRepository");
    $configurator->services()->alias(SubmittedAttendingGuestRepository::class, "App\\Infrastructure\\{$projectionBackend}\\{$projectionBackend}SubmittedAttendingGuestRepository");
    $configurator->services()->alias(SentInviteRepository::class, "App\\Infrastructure\\{$projectionBackend}\\{$projectionBackend}SentInviteRepository");
    $configurator->services()->alias(AvailableFoodChoiceRepository::class, "App\\Infrastructure\\{$projectionBackend}\\{$projectionBackend}AvailableFoodChoiceRepository");
    $configurator->services()->alias(InviteCodeToIdFinder::class, "App\\Infrastructure\\{$projectionBackend}\\{$projectionBackend}InviteCodeToIdFinder");
    $configurator->services()->alias(ChosenFoodChoiceValidator::class, "App\\Infrastructure\\{$projectionBackend}\\{$projectionBackend}ChosenFoodChoiceValidator");
};
