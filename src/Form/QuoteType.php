<?php

namespace App\Form;

use App\Entity\Quote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom *',
                'attr' => ['class' => 'form-control']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom *',
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email *',
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone *',
                'attr' => ['class' => 'form-control']
            ])
            ->add('company', TextType::class, [
                'label' => 'Entreprise',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('productType', ChoiceType::class, [
                'label' => 'Type de produit *',
                'choices' => [
                    'Électronique' => 'electronics',
                    'Textile et mode' => 'textile',
                    'Mobilier et décoration' => 'furniture',
                    'Beauté et bien-être' => 'beauty',
                    'Jouets et jeux' => 'toys',
                    'Articles de sport' => 'sports',
                    'Outils et matériel' => 'tools',
                    'Autre' => 'other'
                ],
                'placeholder' => 'Sélectionnez une catégorie',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un type de produit'
                    ])
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('otherProductType', TextType::class, [
                'label' => 'Précisez le type de produit *',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'display: none;'
                ]
            ])
            ->add('productDescription', TextareaType::class, [
                'label' => 'Description détaillée du produit *',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Veuillez décrire le produit que vous souhaitez importer (caractéristiques, spécifications, etc.)'
                ]
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité estimée *',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1
                ]
            ])
            ->add('budget', NumberType::class, [
                'label' => 'Budget approximatif (€)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ]
            ])
            ->add('timeline', ChoiceType::class, [
                'label' => 'Délai souhaité *',
                'choices' => [
                    'Urgent (moins d\'un mois)' => 'urgent',
                    '1-2 mois' => '1-2months',
                    '3-6 mois' => '3-6months',
                    'Plus de 6 mois' => '6+months',
                    'Flexible' => 'flexible'
                ],
                'placeholder' => 'Sélectionnez un délai',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un délai'
                    ])
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('services', ChoiceType::class, [
                'label' => 'Services requis *',
                'choices' => [
                    'Sourcing de fournisseurs' => 'sourcing',
                    'Contrôle qualité' => 'qualityControl',
                    'Logistique et transport' => 'logistics',
                    'Dédouanement' => 'customs',
                    'Développement de produit' => 'productDevelopment',
                    'Service complet (de la recherche à la livraison)' => 'fullService'
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('additionalInfo', TextareaType::class, [
                'label' => 'Informations complémentaires',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Toute information supplémentaire qui pourrait nous aider à mieux comprendre votre projet'
                ]
            ])
            ->add('referralSource', ChoiceType::class, [
                'label' => 'Comment avez-vous connu nos services ?',
                'choices' => [
                    'Moteur de recherche' => 'search',
                    'Réseaux sociaux' => 'socialMedia',
                    'Recommandation' => 'recommendation',
                    'Autre' => 'other'
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('privacyPolicy', CheckboxType::class, [
                'label' => 'J\'accepte que mes données soient traitées conformément à la politique de confidentialité *',
                'attr' => ['class' => 'form-check-input']
            ])
        ;

        // Gestion dynamique du champ "Autre type de produit"
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (isset($data['productType']) && $data['productType'] === 'other') {
                $form->add('otherProductType', TextType::class, [
                    'label' => 'Précisez le type de produit *',
                    'required' => true,
                    'attr' => ['class' => 'form-control']
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
} 