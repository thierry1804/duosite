<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForgotPasswordOtpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('otpCode', TextType::class, [
            'label' => 'Code reçu par email',
            'attr' => ['autocomplete' => 'one-time-code', 'inputmode' => 'numeric'],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer le code reçu par email.']),
                new Length([
                    'min' => 6,
                    'max' => 6,
                    'exactMessage' => 'Le code doit contenir exactement {{ limit }} caractères.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
