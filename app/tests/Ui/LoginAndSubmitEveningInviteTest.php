<?php declare(strict_types=1);

namespace App\Tests\Ui;

use App\Application\Command\CommandBus;
use App\Application\Command\CreateInvite\CreateInviteCommand;

final class LoginAndSubmitEveningInviteTest extends UiTestCase
{
    /**
     * @testWith ["Postgres", "Postgres"]
     *           ["DynamoDb", "DynamoDb"]
     *           ["Postgres", "DynamoDb"]
     *           ["DynamoDb", "Postgres"]
     *           ["EventStoreDb", "Postgres"]
     *           ["EventStoreDb", "DynamoDb"]
     */
    public function test_login_and_submit_evening_invite(string $eventStoreBackend, string $projectionBackend): void
    {
        $this->givenAppWithBackends($eventStoreBackend, $projectionBackend);
        $invite = $this->givenEveningInvitePresentForOneOfEachGuestType();

        $this->whenWeLogIntoTheInviteAndVisitTheRSVPForm($invite['code']);
        $this->whenWeFillOutAndSubmitTheRSVPFormWithSongChoice($invite);

        $this->thenEmailNotificationSent();
        $this->thenPresentedWithSuccessMessage();
        $this->thenAttendingGuestsPresentWithinAdmin();
        $this->thenSongChoicePresentInAdmin();
    }

    private function givenEveningInvitePresentForOneOfEachGuestType(): array
    {
        $command = new CreateInviteCommand(
            'evening',
            [
                ['type' => 'adult', 'name' => 'Adult name'],
                ['type' => 'child', 'name' => 'Child name'],
                ['type' => 'baby', 'name' => 'Baby name'],
            ]
        );

        self::getContainer()->get(CommandBus::class)->dispatchSync($command);

        return [
            'code' => $command->getCode()->toString(),
            'adultId' => $command->getInvitedGuests()[0]->getId()->toString(),
            'childId' => $command->getInvitedGuests()[1]->getId()->toString(),
            'babyId' => $command->getInvitedGuests()[2]->getId()->toString(),
        ];
    }

    private function whenWeLogIntoTheInviteAndVisitTheRSVPForm(string $code): void
    {
        $this->client->request('GET', '/');
        $this->client->submitForm('Login', ['login[code]' => $code]);
        $this->client->followRedirect();
        $this->client->clickLink('RSVP');
    }

    private function whenWeFillOutAndSubmitTheRSVPFormWithSongChoice(array $invite): void
    {
        $form = $this->client->getCrawler()->selectButton('Submit')->form();

        $form['invite_rsvp[guests][' . $invite['adultId'] . '][attending]'] = true;
        $form['invite_rsvp[guests][' . $invite['childId'] . '][attending]'] = true;
        $form['invite_rsvp[guests][' . $invite['babyId'] . '][attending]'] = true;

        $form['invite_rsvp[songs][0][artist]'] = 'Sample Artist';
        $form['invite_rsvp[songs][0][track]'] = 'Sample Track';

        $this->client->submit($form);
    }

    /** Required to be asserted before redirect is followed */
    private function thenEmailNotificationSent(): void
    {
        self::assertEmailCount(1);
        $email = $this->getMailerMessage(0);
        self::assertEmailHeaderSame($email, 'Subject', 'An invite has been submitted!');
    }

    private function thenPresentedWithSuccessMessage(): void
    {
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Thank you for submitting your RSVP.', $this->client->getResponse()->getContent());
    }

    private function thenAttendingGuestsPresentWithinAdmin(): void
    {
        $this->client->request('GET', '/admin/attending-guests', [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => \getenv('ADMIN_PASSWORD')]);

        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('Adult name', $content);
        self::assertStringContainsString('Child name', $content);
        self::assertStringContainsString('Baby name', $content);
    }

    private function thenSongChoicePresentInAdmin(): void
    {
        $this->client->request('GET', '/admin/song-choices', [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => \getenv('ADMIN_PASSWORD')]);

        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('Sample Artist', $content);
        self::assertStringContainsString('Sample Track', $content);
    }
}
