<?php declare(strict_types=1);

namespace App\Ui\Http\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class LoginType extends AbstractType
{
    private const CODE_LENGTH = 4;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'attr' => [
                    'placeholder' => 'Invite code',
                    'maxlength' => self::CODE_LENGTH,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => \sprintf('Please enter your %d character code', self::CODE_LENGTH),
                    ]),
                    new Assert\Length([
                        'min' => self::CODE_LENGTH,
                        'max' => self::CODE_LENGTH,
                        'exactMessage' => 'Please enter your {{ limit }} character code',
                    ]),
                ],
                'error_bubbling' => true,
            ])
            ->add('login', SubmitType::class);
    }
}
