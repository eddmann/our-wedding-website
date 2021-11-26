<?php declare(strict_types=1);

namespace App\Ui\Http\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class InviteRsvpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('guests', GuestCollectionType::class, [
                'guests' => $options['invite']['guests'],
            ])
            ->add('songs', CollectionType::class, [
                'entry_type' => SongChoiceType::class,
                'data' => [['track' => '', 'artist' => ''], ['track' => '', 'artist' => '']],
                'constraints' => new Assert\Callback(static function (array $submission, ExecutionContextInterface $context): void {
                    $missing = \array_filter($submission, static fn (array $song) => empty($song['artist']) !== empty($song['track']));

                    if (empty($missing)) {
                        return;
                    }

                    $context->addViolation('Please specify both a song choice artist and track');
                }),
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('invite', []);
        $resolver->setAllowedTypes('invite', 'array');
    }
}
