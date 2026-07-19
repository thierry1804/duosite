<?php

namespace App\Form;

use App\Entity\QuoteItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * Ligne article du wizard admin : demande + prix unitaire (offre) en une seule saisie.
 */
class AdminManualArticleLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productType', ChoiceType::class, [
                'label' => 'Type de produit',
                'choices' => [
                    'Modes et accessoires' => 'Modes et accessoires',
                    'Lingerie' => 'Lingerie',
                    'Bijoux inoxydable' => 'Bijoux inoxydable',
                    'Article de bébé' => 'Article de bébé',
                    'Électroménager' => 'Électroménager',
                    'Ustensiles de cuisine' => 'Ustensiles de cuisine',
                    'Linge de maison' => 'Linge de maison',
                    'Meuble d\'intérieur' => 'Meuble d\'intérieur',
                    'Décoration' => 'Décoration',
                    'Meuble de jardin' => 'Meuble de jardin',
                    'Article pour les animaux domestiques' => 'Article pour les animaux domestiques',
                    'Accessoires mobiles' => 'Accessoires mobiles',
                    'Article de voyage' => 'Article de voyage',
                    'Équipements de cuisine et de restauration' => 'Équipements de cuisine et de restauration',
                    'Équipements de pâtisserie et de boulangerie' => 'Équipements de pâtisserie et de boulangerie',
                    'Jouets' => 'Jouets',
                    'Articles de sport' => 'Articles de sport',
                ],
                'placeholder' => 'Sélectionnez un type de produit',
                'constraints' => [
                    new NotBlank(['message' => 'Le type de produit est obligatoire']),
                ],
                'attr' => ['class' => 'product-type-select form-select'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire']),
                    new Length([
                        'min' => QuoteItem::DESCRIPTION_MIN_LENGTH,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Caractéristiques, matériaux, couleurs…',
                    'minlength' => (string) QuoteItem::DESCRIPTION_MIN_LENGTH,
                ],
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'html5' => true,
                'data' => 1,
                'empty_data' => '1',
                'constraints' => [
                    new NotBlank(['message' => 'La quantité est obligatoire']),
                    new Positive(['message' => 'La quantité doit être positive']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Qté',
                ],
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Prix unitaire (RMB)',
                'currency' => 'RMB',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le prix unitaire est obligatoire']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                ],
            ])
            ->add('dimensions', TextType::class, [
                'label' => 'Dimensions / taille (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 10 × 15 × 5 cm',
                ],
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Poids (optionnel)',
                'required' => false,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'g ou kg',
                    'step' => '0.01',
                ],
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo (optionnel)',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Image JPEG, PNG, GIF ou WEBP',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/png,image/jpeg,image/jpg,image/webp,image/gif',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
