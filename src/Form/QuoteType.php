<?php

namespace App\Form;

use App\Entity\Quote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Votre prénom'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Votre nom'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Votre email'
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'placeholder' => 'Votre numéro de téléphone'
                ]
            ])
            ->add('company', TextType::class, [
                'label' => 'Entreprise (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre entreprise'
                ]
            ])
            ->add('timeline', ChoiceType::class, [
                'label' => 'Délai souhaité',
                'choices' => [
                    'Urgent (moins d\'un mois)' => 'Urgent (moins d\'un mois)',
                    '1-3 mois' => '1-3 mois',
                    '3-6 mois' => '3-6 mois',
                    'Plus de 6 mois' => 'Plus de 6 mois',
                    'Pas de délai particulier' => 'Pas de délai particulier'
                ],
                'placeholder' => 'Sélectionnez un délai',
            ])
            ->add('services', ChoiceType::class, [
                'label' => 'Services souhaités',
                'choices' => [
                    'Sourcing de produits' => 'Sourcing de produits',
                    'Négociation avec les fournisseurs' => 'Négociation avec les fournisseurs',
                    'Contrôle qualité' => 'Contrôle qualité',
                    'Logistique et transport' => 'Logistique et transport',
                    'Dédouanement' => 'Dédouanement',
                    'Accompagnement complet' => 'Accompagnement complet'
                ],
                'multiple' => true,
                'expanded' => true
            ])
            ->add('additionalInfo', TextareaType::class, [
                'label' => 'Informations complémentaires (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Toute information supplémentaire qui pourrait nous aider à traiter votre demande',
                    'rows' => 4
                ]
            ])
            ->add('referralSource', ChoiceType::class, [
                'label' => 'Comment nous avez-vous connu ? (optionnel)',
                'required' => false,
                'choices' => [
                    'Recherche Google' => 'Recherche Google',
                    'Réseaux sociaux' => 'Réseaux sociaux',
                    'Recommandation' => 'Recommandation',
                    'Autre' => 'Autre'
                ],
                'placeholder' => 'Sélectionnez une option',
            ])
            ->add('privacyPolicy', CheckboxType::class, [
                'label' => 'J\'accepte la politique de confidentialité',
                'label_attr' => [
                    'class' => 'checkbox-custom-label'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Vous devez accepter la politique de confidentialité'
                    ])
                ]
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => QuoteItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Produits',
                'attr' => [
                    'class' => 'quote-items-collection'
                ]
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