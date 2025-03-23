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
                'label' => 'Prix minimum - RMB',
                'currency' => ' RMB',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prix min'
                ],
                'row_attr' => [
                    'class' => 'col-md-6 px-1'
                ]
            ])
            ->add('maxPrice', MoneyType::class, [
                'label' => 'Prix maximum - RMB',
                'currency' => ' RMB',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Prix max'
                ],
                'row_attr' => [
                    'class' => 'col-md-6 px-1'
                ]
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Infos complémentaires',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Commentaires sur ce produit'
                ]
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Images',
                'multiple' => true,
                'required' => false,
                'mapped' => true,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ]
            ])
            ->add('dimensions', TextareaType::class, [
                'label' => 'Dimensions',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 1,
                    'placeholder' => 'Ex: 10cm x 15cm x 5cm'
                ],
                'row_attr' => [
                    'class' => 'col-md-6 px-1'
                ]
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Poids (g)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Poids en g'
                ],
                'row_attr' => [
                    'class' => 'col-md-6 px-1'
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