<?php declare(strict_types=1);

namespace App\Ui\Http\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GuestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('attending', ChoiceType::class, [
            'placeholder' => 'Will you be attending?',
            'choices' => [
                'I will be attending' => true,
                'I will not be attending' => false,
            ],
        ]);

        foreach ($options['foodChoices'] as $course => $choices) {
            $builder->add("{$course}Id", ChoiceType::class, [
                'placeholder' => "Choose a {$course}",
                'choices' => \array_combine(
                    \array_column($choices, 'name'),
                    \array_column($choices, 'id')
                ),
            ]);
        }

        if (! empty($options['foodChoices'])) {
            $builder->add('dietaryRequirements', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Please let us know if you have any dietary requirement and/or food allergy that we need to make the catering staff aware of.',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('foodChoices', []);
        $resolver->setAllowedTypes('foodChoices', 'array');
    }
}
