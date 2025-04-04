<?php

namespace App\Form;

use App\Entity\ProductProposal;
use App\Entity\QuoteItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Doctrine\ORM\EntityRepository;

class ProductProposalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quoteItem', EntityType::class, [
                'label' => 'Produit / Article',
                'class' => QuoteItem::class,
                'choice_label' => function (QuoteItem $quoteItem) {
                    static $counter = 0;
                    $counter++;
                    return '#' . $counter . ' - ' . $quoteItem->getProductType() . ' - ' . substr($quoteItem->getDescription(), 0, 50) . 
                           (strlen($quoteItem->getDescription()) > 50 ? '...' : '');
                },
                'placeholder' => 'Sélectionnez un produit',
                'choices' => $options['quote_items'],
                'required' => true,
                'attr' => [
                    'class' => 'form-select product-select'
                ]
            ])
            ->add('minPrice', MoneyType::class, [
                'label' => 'Prix minimum (RMB)',
                'currency' => 'RMB',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '13.00'
                ],
                'row_attr' => [
                    'class' => 'col-md-6'
                ]
            ])
            ->add('maxPrice', MoneyType::class, [
                'label' => 'PU',
                'currency' => 'RMB',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '130.00'
                ],
                'row_attr' => [
                'class' => 'col-md-12'
                ]
            ])
            ->add('dimensions', TextType::class, [
                'label' => 'Dimensions / Taille',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 10cm x 15cm x 5cm; S, M, L, XL, etc.'
                ],
                'row_attr' => [
                    'class' => 'col-md-12'
                ]
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Poids (g)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '190'
                ],
                'row_attr' => [
                    'class' => 'col-md-12'
                ]
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Infos complémentaires',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'j lkjlkjlkjhlkhl'
                ]
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Images',
                'multiple' => true,
                'required' => false,
                'mapped' => true,
                'attr' => [
                    'class' => 'form-control image-upload-input',
                    'accept' => 'image/*',
                    'style' => 'display: none;'
                ],
                'row_attr' => [
                    'class' => 'image-upload-container'
                ]
            ])
            ->add('removedImages', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'allow_add' => true,
                'mapped' => false,
                'required' => false,
                'prototype' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductProposal::class,
            'quote_items' => [],
        ]);
        
        $resolver->setAllowedTypes('quote_items', 'array');
    }
} 