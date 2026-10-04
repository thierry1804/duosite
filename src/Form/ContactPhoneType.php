<?php

namespace App\Form;

use App\Entity\ContactPhone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactPhoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Ex. Thierry'],
            ])
            ->add('number', TextType::class, [
                'label' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro est obligatoire.']),
                ],
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => '+261 XX XX XXX XX'],
            ])
            ->add('isWhatsapp', CheckboxType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['class' => 'form-check-input', 'title' => 'WhatsApp'],
            ])
            ->add('isPrimary', CheckboxType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['class' => 'form-check-input', 'title' => 'Principal (Mobile Money)'],
            ])
            ->add('position', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactPhone::class,
        ]);
    }
}
