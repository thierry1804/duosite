<?php

namespace App\Form;

use App\Entity\QuoteOffer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class QuoteOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'offre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Offre Standard, Offre Premium...'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description détaillée de l\'offre'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => 'draft',
                    'En attente d\'envoi' => 'pending',
                    'Envoyée' => 'sent',
                    'Acceptée' => 'accepted',
                    'Refusée' => 'declined'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('rmbMgaExchangeRate', NumberType::class, [
                'label' => 'Taux de change RMB/MGA <a href="javascript(void);" title="Taux de change RMB/MGA au moment de la création de l\'offre."><i data-lucide="help-circle" class="text-info" style="width: 16px; height: 16px;" data-bs-toggle="tooltip" data-bs-placement="top" title="Taux de conversion entre le Yuan chinois (RMB) et l\'Ariary malgache (MGA). Ce taux est utilisé pour les calculs de conversion dans l\'offre."></i></a>',
                'label_html' => true,
                'required' => false,
                'scale' => 6,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control exchange-rate-field',
                    'placeholder' => 'Ex: 556.123456',
                    'step' => '0.000001',
                    'data-api-url' => 'https://open.er-api.com/v6/latest/CNY'
                ],
            ])
            ->add('productProposals', CollectionType::class, [
                'entry_type' => ProductProposalType::class,
                'entry_options' => [
                    'label' => false,
                    'quote_items' => $options['quote_items'] ?? [],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'attr' => [
                    'class' => 'product-proposal-collection'
                ]
            ])
            ->add('shippingOptions', CollectionType::class, [
                'entry_type' => ShippingOptionType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'attr' => [
                    'class' => 'shipping-option-collection'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteOffer::class,
            'quote_items' => []
        ]);
        
        $resolver->setAllowedTypes('quote_items', 'array');
    }
} 