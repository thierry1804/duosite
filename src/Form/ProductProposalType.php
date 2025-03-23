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
use Doctrine\ORM\EntityRepository;

class ProductProposalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quoteItem', EntityType::class, [
                'class' => QuoteItem::class,
                'choice_label' => function (QuoteItem $quoteItem) {
                    return $quoteItem->getProductType() . ' - ' . substr($quoteItem->getDescription(), 0, 50) . 
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
                'label' => 'Prix minimum',
                'currency' => 'EUR',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prix min'
                ]
            ])
            ->add('maxPrice', MoneyType::class, [
                'label' => 'Prix maximum',
                'currency' => 'EUR',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prix max'
                ]
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Commentaires',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Commentaires sur ce produit'
                ]
            ])
            ->add('dimensions', TextareaType::class, [
                'label' => 'Dimensions',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Ex: 10cm x 15cm x 5cm'
                ]
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Poids (kg)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Poids en kg'
                ]
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Images',
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ]
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