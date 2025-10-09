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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
            ->add('estimatedDeliveryDays', IntegerType::class, [
                'label' => 'Délai max de livraison (jours)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Délai en jours',
                    'min' => 1
                ],
                'row_attr' => [
                    'class' => 'col-md-6 mb-3'
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Tarif en MGA',
                'currency' => ' MGA',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Tarif en MGA'
                ],
                'row_attr' => [
                    'class' => 'col-md-6 mb-3'
                ]
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        
        // Ajouter une classe row pour permettre l'alignement horizontal
        $view->vars['attr']['class'] = isset($view->vars['attr']['class']) 
            ? $view->vars['attr']['class'] . ' row' 
            : 'row';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShippingOption::class,
        ]);
    }
} 