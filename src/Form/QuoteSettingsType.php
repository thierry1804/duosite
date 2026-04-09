<?php

namespace App\Form;

use App\Entity\QuoteSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class QuoteSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('itemPrice', IntegerType::class, [
                'label' => 'Prix par article (en Ariary)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un prix par article',
                    ]),
                    new Positive([
                        'message' => 'Le prix par article doit être positif',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'class' => 'form-control',
                ],
                'help' => 'Quel est le prix à payer pour chaque article au-delà de la limite gratuite ?',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteSettings::class,
        ]);
    }
} 