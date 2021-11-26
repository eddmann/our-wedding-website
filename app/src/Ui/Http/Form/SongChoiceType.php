<?php declare(strict_types=1);

namespace App\Ui\Http\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class SongChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('artist', TextType::class, ['attr' => ['placeholder' => 'Artist']])
            ->add('track', TextType::class, ['attr' => ['placeholder' => 'Track']]);
    }
}
