<?php

namespace App\Form;

use App\Entity\SocialLink;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class SocialLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('network', ChoiceType::class, [
                'label' => 'Réseau',
                'choices' => array_flip(SocialLink::NETWORKS),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL',
                'default_protocol' => 'https',
                'constraints' => [
                    new NotBlank(['message' => 'L\'URL est obligatoire.']),
                    new Url(['message' => 'URL invalide.']),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('position', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SocialLink::class,
        ]);
    }
}
