<?php

namespace App\Form;

use App\Entity\ShippingOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ShippingOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du mode d\'expédition',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Standard, Express, Premium...'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Description détaillée du mode d\'expédition'
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prix'
                ]
            ])
            ->add('estimatedDeliveryDays', IntegerType::class, [
                'label' => 'Délai de livraison estimé (jours)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Délai en jours',
                    'min' => 1
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShippingOption::class,
        ]);
    }
} 