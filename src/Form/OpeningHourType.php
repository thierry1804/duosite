<?php

namespace App\Form;

use App\Entity\OpeningHour;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpeningHourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dayOfWeek', HiddenType::class)
            ->add('isClosed', CheckboxType::class, [
                'label' => 'Fermé',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('openTime', TimeType::class, [
                'label' => 'Ouverture',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('closeTime', TimeType::class, [
                'label' => 'Fermeture',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var OpeningHour|null $hour */
            $hour = $event->getData();
            $form = $event->getForm();
            if (!$hour instanceof OpeningHour || $hour->isClosed()) {
                return;
            }

            if (!$hour->getOpenTime() || !$hour->getCloseTime()) {
                $form->addError(new FormError('Indiquez les heures d\'ouverture et de fermeture.'));

                return;
            }

            if ($hour->getOpenTime() >= $hour->getCloseTime()) {
                $form->addError(new FormError('L\'heure d\'ouverture doit être antérieure à la fermeture.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpeningHour::class,
        ]);
    }
}
