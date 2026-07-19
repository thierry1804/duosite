<?php

namespace App\Form;

use App\Entity\ProductProposal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Proposition produit pour le wizard admin : lie l'article via un index
 * (les QuoteItem n'ont pas encore d'id avant le flush).
 */
class AdminManualProductProposalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $indexChoices = [];
        for ($i = 0; $i < 20; ++$i) {
            $indexChoices['Article #' . ($i + 1)] = $i;
        }

        $builder
            ->add('quoteItemIndex', ChoiceType::class, [
                'label' => 'Article de la demande',
                'mapped' => false,
                'required' => true,
                'choices' => $indexChoices,
                'placeholder' => 'Sélectionnez un article',
                'constraints' => [
                    new NotNull(['message' => 'Sélectionnez un article']),
                ],
                'attr' => [
                    'class' => 'form-select product-proposal-quote-item-index',
                ],
                'row_attr' => [
                    'class' => 'col-12 mb-0 product-proposal-field-quote-item',
                ],
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Images du produit',
                'multiple' => true,
                'required' => false,
                'mapped' => true,
                'attr' => [
                    'class' => 'form-control image-upload-input product-proposal-file-input',
                    'accept' => 'image/png,image/jpeg,image/jpg,image/webp,image/gif',
                ],
                'row_attr' => [
                    'class' => 'col-12 mb-0 product-proposal-field-images',
                ],
            ])
            ->add('maxPrice', MoneyType::class, [
                'label' => 'Prix fournisseur',
                'currency' => 'RMB',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '13.00',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-md-4 mb-0 product-proposal-field-price',
                ],
            ])
            ->add('dimensions', TextType::class, [
                'label' => 'Dimensions / Taille',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 10cm x 15cm x 5cm',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-md-4 mb-0 product-proposal-field-dimensions',
                ],
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Poids',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '190',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-md-4 mb-0 product-proposal-field-weight',
                ],
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Infos complémentaires',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Matériaux, couleurs, particularités...',
                ],
                'row_attr' => [
                    'class' => 'col-12 mb-0 product-proposal-field-comments',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductProposal::class,
        ]);
    }
}
