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
                'label' => 'Type de produit *',
                'choices' => [
                    'Vêtements' => 'Vêtements',
                    'Électronique' => 'Électronique',
                    'Mobilier' => 'Mobilier',
                    'Accessoires' => 'Accessoires',
                    'Matériel industriel' => 'Matériel industriel',
                    'Autre' => 'Autre'
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
            ->add('otherProductType', TextType::class, [
                'label' => 'Précisez le type de produit',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Précisez le type de produit',
                    'class' => 'other-product-type',
                    'style' => 'display: none;'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du produit *',
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
                'label' => 'Quantité *',
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
                'currency' => 'EUR',
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

        // Afficher le champ "otherProductType" uniquement si "productType" est "Autre"
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (isset($data['productType']) && $data['productType'] === 'Autre') {
                $form->add('otherProductType', TextType::class, [
                    'label' => 'Précisez le type de produit',
                    'required' => true,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez préciser le type de produit'
                        ])
                    ],
                    'attr' => [
                        'placeholder' => 'Précisez le type de produit',
                        'class' => 'other-product-type'
                    ]
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteItem::class,
        ]);
    }
} 