<?php declare(strict_types=1);

namespace App\Tests\Ui;

use App\Application\Command\CommandBus;
use App\Application\Command\CreateFoodChoice\CreateFoodChoiceCommand;
use App\Application\Command\CreateInvite\CreateInviteCommand;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginAndSubmitDayInviteTest extends WebTestCase
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

    public function test_login_and_submit_day_invite(): void
    {
        $adultFoodChoices = $this->givenFoodChoicesAvailableForAdults();
        $childFoodChoices = $this->givenFoodChoicesAvailableForChildren();
        $invite = $this->givenDayInvitePresentForOneOfEachGuestType();

        $this->whenWeLogIntoTheInviteAndVisitTheRSVPForm($invite['code']);
        $this->whenWeFillOutAndSubmitTheRSVPFormWithFoodAndSongChoices($invite, $adultFoodChoices, $childFoodChoices);

        $this->thenEmailNotificationSent();
        $this->thenPresentedWithSuccessMessage();
        $this->thenAttendingGuestsPresentWithinAdmin();
        $this->thenSongChoicePresentInAdmin();
    }

    private function givenFoodChoicesAvailableForAdults(): array
    {
        return [
            'starterId' => $this->createFoodChoice('Adult Starter', 'starter', 'adult'),
            'mainId' => $this->createFoodChoice('Adult Main', 'main', 'adult'),
            'dessertId' => $this->createFoodChoice('Adult Dessert', 'dessert', 'adult'),
        ];
    }

    private function givenFoodChoicesAvailableForChildren(): array
    {
        return [
            'starterId' => $this->createFoodChoice('Child Starter', 'starter', 'child'),
            'mainId' => $this->createFoodChoice('Child Main', 'main', 'child'),
            'dessertId' => $this->createFoodChoice('Child Dessert', 'dessert', 'child'),
        ];
    }

    private function createFoodChoice(string $name, string $course, string $guestType): string
    {
        $command = new CreateFoodChoiceCommand($guestType, $course, $name);

        self::getContainer()->get(CommandBus::class)->dispatch($command);

        return $command->getId()->toString();
    }

    private function givenDayInvitePresentForOneOfEachGuestType(): array
    {
        $command = new CreateInviteCommand(
            'day',
            [
                ['type' => 'adult', 'name' => 'Adult name'],
                ['type' => 'child', 'name' => 'Child name'],
                ['type' => 'baby', 'name' => 'Baby name'],
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

    private function whenWeFillOutAndSubmitTheRSVPFormWithFoodAndSongChoices(array $invite, array $adultFoodChoices, array $childFoodChoices): void
    {
        $form = $this->client->getCrawler()->selectButton('Submit')->form();

        $form['invite_rsvp[guests][' . $invite['adultId'] . '][attending]'] = true;
        $form['invite_rsvp[guests][' . $invite['adultId'] . '][starterId]'] = $adultFoodChoices['starterId'];
        $form['invite_rsvp[guests][' . $invite['adultId'] . '][mainId]'] = $adultFoodChoices['mainId'];
        $form['invite_rsvp[guests][' . $invite['adultId'] . '][dessertId]'] = $adultFoodChoices['dessertId'];
        $form['invite_rsvp[guests][' . $invite['adultId'] . '][dietaryRequirements]'] = 'Sample dietary requirements';

        $form['invite_rsvp[guests][' . $invite['childId'] . '][attending]'] = true;
        $form['invite_rsvp[guests][' . $invite['childId'] . '][starterId]'] = $childFoodChoices['starterId'];
        $form['invite_rsvp[guests][' . $invite['childId'] . '][mainId]'] = $childFoodChoices['mainId'];
        $form['invite_rsvp[guests][' . $invite['childId'] . '][dessertId]'] = $childFoodChoices['dessertId'];

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
        self::assertStringContainsString('Adult Starter, Adult Main, Adult Dessert', $content);
        self::assertStringContainsString('Child name', $content);
        self::assertStringContainsString('Child Starter, Child Main, Child Dessert', $content);
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
