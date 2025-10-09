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
            ->add('freeItemsLimit', IntegerType::class, [
                'label' => 'Nombre d\'articles gratuits pour le premier devis',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nombre d\'articles gratuits',
                    ]),
                    new Positive([
                        'message' => 'Le nombre d\'articles gratuits doit être positif',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'class' => 'form-control',
                ],
                'help' => 'Pour le premier devis d\'un utilisateur, combien d\'articles sont gratuits ?',
            ])
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