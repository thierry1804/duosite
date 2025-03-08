<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Votre prénom',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Votre nom',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Votre email',
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre numéro de téléphone',
                ],
            ])
            ->add('company', TextType::class, [
                'label' => 'Entreprise',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre entreprise (optionnel)',
                ],
            ]);

        // Ajouter le champ de mot de passe avec des contraintes différentes selon le contexte
        $passwordConstraints = [
            new Length([
                'min' => 8,
                'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                'max' => 4096,
            ]),
        ];

        if (!$isEdit) {
            // En création, le mot de passe est obligatoire
            $passwordConstraints[] = new NotBlank([
                'message' => 'Veuillez entrer un mot de passe',
            ]);
        }

        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'required' => !$isEdit,
            'first_options' => [
                'label' => 'Mot de passe',
                'attr' => [
                    'placeholder' => $isEdit ? 'Laissez vide pour conserver le mot de passe actuel' : 'Votre mot de passe',
                ],
                'constraints' => $passwordConstraints,
            ],
            'second_options' => [
                'label' => 'Confirmer le mot de passe',
                'attr' => [
                    'placeholder' => $isEdit ? 'Laissez vide pour conserver le mot de passe actuel' : 'Confirmez votre mot de passe',
                ],
            ],
            'invalid_message' => 'Les mots de passe ne correspondent pas.',
        ]);

        if (!$isEdit) {
            $builder->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions d\'utilisation',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation.',
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
} 