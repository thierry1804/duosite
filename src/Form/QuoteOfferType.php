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