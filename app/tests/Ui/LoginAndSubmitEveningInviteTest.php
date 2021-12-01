<?php declare(strict_types=1);

namespace App\Tests\Ui;

use App\Application\Command\CommandBus;
use App\Application\Command\CreateInvite\CreateInviteCommand;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginAndSubmitEveningInviteTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();

        $this->client->disableReboot();

        self::getContainer()->get(Connection::class)->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::getContainer()->get(Connection::class)->rollBack();

        parent::tearDown();
    }

    public function test_login_and_submit_evening_invite(): void
    {
        $invite = $this->givenEveningInvitePresentForOneOfEachGuestType();

        $this->whenWeLogIntoTheInviteAndVisitTheRSVPForm($invite['code']);
        $this->whenWeFillOutAndSubmitTheRSVPFormWithSongChoice($invite);

        $this->thenSuccessfullySubmittedTheRSVP();
        $this->thenAttendingGuestsPresentWithinAdmin();
        $this->thenSongChoicePresentInAdmin();
    }

    private function givenEveningInvitePresentForOneOfEachGuestType(): array
    {
        $command = new CreateInviteCommand(
            'evening',
            [
                ['type' => 'adult', 'name' => 'Adult'],
                ['type' => 'child', 'name' => 'Child'],
                ['type' => 'baby', 'name' => 'Baby'],
            ]
        );

        self::getContainer()->get(CommandBus::class)->dispatch($command);

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
        $this->client->followRedirect();
    }

    private function thenSuccessfullySubmittedTheRSVP(): void
    {
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Thank you for submitting your RSVP.', $this->client->getResponse()->getContent());
    }

    private function thenAttendingGuestsPresentWithinAdmin(): void
    {
        $this->client->request('GET', '/admin/attending-guests', [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => \getenv('ADMIN_PASSWORD')]);

        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('Adult', $content);
        self::assertStringContainsString('Child', $content);
        self::assertStringContainsString('Baby', $content);
    }

    private function thenSongChoicePresentInAdmin(): void
    {
        $this->client->request('GET', '/admin/song-choices', [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => \getenv('ADMIN_PASSWORD')]);

        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('Sample Artist', $content);
        self::assertStringContainsString('Sample Track', $content);
    }
}
