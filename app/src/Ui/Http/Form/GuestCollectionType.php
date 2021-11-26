<?php declare(strict_types=1);

namespace App\Ui\Http\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class GuestCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['guests'] as $guest) {
            $builder->add($guest['id'], GuestType::class, [
                'label' => $guest['name'],
                'foodChoices' => $guest['foodChoices'],
                'constraints' => new Assert\Callback($this->buildConstraintsFor($guest)),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('guests', []);
        $resolver->setAllowedTypes('guests', 'array');
    }

    private function buildConstraintsFor(array $guest): \Closure
    {
        return static function (array $submission, ExecutionContextInterface $context) use ($guest): void {
            if ($submission['attending'] === null) {
                $context->addViolation(\sprintf('Please select whether %s is attending or not', $guest['name']));

                return;
            }

            if ($submission['attending'] === false || empty($guest['foodChoices'])) {
                return;
            }

            $missingFoodChoices = [];
            foreach ($guest['foodChoices'] as $course => $choices) {
                if (! \in_array($submission["{$course}Id"], \array_column($choices, 'id'), true)) {
                    $missingFoodChoices[] = $course === 'desert' ? 'dessert' : $course;
                }
            }

            if (empty($missingFoodChoices)) {
                return;
            }

            $context->addViolation(\sprintf('Please select a %s for %s', \implode(', ', $missingFoodChoices), $guest['name']));
        };
    }
}
