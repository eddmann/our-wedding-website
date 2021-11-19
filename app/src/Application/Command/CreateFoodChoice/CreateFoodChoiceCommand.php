<?php declare(strict_types=1);

namespace App\Application\Command\CreateFoodChoice;

use App\Application\Command\Command;
use App\Domain\Model\FoodChoice\{FoodChoiceId, FoodChoiceName, FoodCourse};
use App\Domain\Model\Shared\GuestType;

final class CreateFoodChoiceCommand implements Command
{
    private FoodChoiceId $id;
    private FoodChoiceName $name;
    private FoodCourse $course;
    private GuestType $guestType;

    public function __construct(string $guestType, string $course, string $name)
    {
        $this->id = FoodChoiceId::generate();
        $this->guestType = GuestType::fromString($guestType);
        $this->course = FoodCourse::fromString($course);
        $this->name = FoodChoiceName::fromString($name);
    }

    public function getId(): FoodChoiceId
    {
        return $this->id;
    }

    public function getGuestType(): GuestType
    {
        return $this->guestType;
    }

    public function getCourse(): FoodCourse
    {
        return $this->course;
    }

    public function getName(): FoodChoiceName
    {
        return $this->name;
    }
}
