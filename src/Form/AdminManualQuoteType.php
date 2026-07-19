<?php

namespace App\Form;

use App\Entity\Quote;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminManualQuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('selectedUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user): string {
                    $label = $user->getFullName() . ' — ' . $user->getEmail();
                    if ($user->getPhone()) {
                        $label .= ' (' . $user->getPhone() . ')';
                    }

                    return $label;
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.lastName', 'ASC')
                        ->addOrderBy('u.firstName', 'ASC');
                },
                'placeholder' => 'Saisie manuelle (nouveau ou existant par email/tél)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-select',
                    'data-user-select' => '1',
                ],
                'label' => 'Client existant',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Prénom', 'class' => 'form-control', 'data-contact-field' => 'firstName'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Nom', 'class' => 'form-control', 'data-contact-field' => 'lastName'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Email', 'class' => 'form-control', 'data-contact-field' => 'email'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['placeholder' => 'Téléphone', 'class' => 'form-control', 'data-contact-field' => 'phone'],
            ])
            ->add('company', TextType::class, [
                'label' => 'Entreprise (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Entreprise', 'class' => 'form-control', 'data-contact-field' => 'company'],
            ])
            ->add('shippingMethod', ChoiceType::class, [
                'label' => 'Choix de l\'envoi',
                'choices' => [
                    'Envoi maritime (délai estimé: 50-70 jours)' => 'maritime',
                    'Envoi aérien express (délai estimé: 3-5 jours)' => 'aerien_express',
                    'Envoi aérien normal (délai estimé: 10-20 jours)' => 'aerien_normal',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => true,
                'attr' => ['class' => 'shipping-method-options'],
            ])
            ->add('additionalInfo', TextareaType::class, [
                'label' => 'Informations complémentaires (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Informations utiles pour le traitement',
                    'rows' => 3,
                    'class' => 'form-control',
                ],
            ])
            ->add('transactionReference', TextType::class, [
                'label' => 'Référence de paiement',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Obligatoire si plus de 2 articles',
                ],
            ])
            ->add('paymentConfirmed', CheckboxType::class, [
                'label' => 'Paiement confirmé (requis pour envoyer l\'offre si > 2 articles)',
                'required' => false,
                'mapped' => false,
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => QuoteItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Produits',
                'attr' => ['class' => 'quote-items-collection'],
                'prototype' => true,
            ])
            // Offre (champs non mappés sur Quote — construits dans le contrôleur)
            ->add('offerTitle', TextType::class, [
                'label' => 'Titre de l\'offre',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Offre Standard',
                ],
            ])
            ->add('offerDescription', TextareaType::class, [
                'label' => 'Description de l\'offre',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('rmbMgaExchangeRate', NumberType::class, [
                'label' => 'Taux de change RMB/MGA',
                'mapped' => false,
                'required' => false,
                'scale' => 6,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control exchange-rate-field',
                    'placeholder' => 'Ex: 556.123456',
                    'step' => '0.000001',
                ],
            ])
            ->add('productProposals', CollectionType::class, [
                'entry_type' => AdminManualProductProposalType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'label' => false,
                'prototype' => true,
                'attr' => ['class' => 'product-proposal-collection'],
            ])
            ->add('shippingOptions', CollectionType::class, [
                'entry_type' => ShippingOptionType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'label' => false,
                'prototype' => true,
                'attr' => ['class' => 'shipping-option-collection'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}
