<?php

namespace App\Form;

use App\Entity\SiteContactSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class SiteContactSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('addressLines', TextareaType::class, [
                'label' => 'Adresse (une ligne par ligne)',
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse est obligatoire.']),
                ],
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email public',
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire.']),
                    new Email(['message' => 'Email invalide.']),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('whatsappDefaultMessage', TextType::class, [
                'label' => 'Message WhatsApp par défaut',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('mapEmbedUrl', UrlType::class, [
                'label' => 'URL iframe Google Maps',
                'required' => false,
                'default_protocol' => 'https',
                'empty_data' => null,
                'constraints' => [
                    new Url(['message' => 'URL de carte invalide.']),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('phones', CollectionType::class, [
                'entry_type' => ContactPhoneType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
            ])
            ->add('openingHours', CollectionType::class, [
                'entry_type' => OpeningHourType::class,
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('socialLinks', CollectionType::class, [
                'entry_type' => SocialLinkType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteContactSettings::class,
        ]);
    }
}
