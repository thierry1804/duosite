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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class QuoteItemType extends AbstractType
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
                    'Articles de sport' => 'Articles de sport'
                ],
                'placeholder' => 'Sélectionnez un type de produit',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le type de produit est obligatoire'
                    ])
                ],
                'attr' => [
                    'class' => 'product-type-select'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du produit',
                'constraints' => [
                    new NotBlank([
                        'message' => 'La description du produit est obligatoire'
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Décrivez le produit en détail (caractéristiques, dimensions, matériaux, etc.)',
                    'rows' => 4
                ]
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'La quantité est obligatoire'
                    ]),
                    new Positive([
                        'message' => 'La quantité doit être positive'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Quantité souhaitée',
                    'min' => 1
                ]
            ])
            ->add('budget', MoneyType::class, [
                'label' => 'Budget (facultatif)',
                'required' => false,
                'currency' => 'MGA',
                'attr' => [
                    'placeholder' => 'Budget estimé'
                ]
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo du produit (facultatif)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WEBP)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control-file'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteItem::class,
        ]);
    }
} 