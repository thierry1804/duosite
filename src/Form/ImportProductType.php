<?php

namespace App\Form;

use App\Entity\ImportProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ImportProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 002'],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (MGA)',
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('availableColorsText', TextType::class, [
                'label' => 'Couleurs disponibles (séparées par des virgules)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Noir, Blanc, Bleu'],
                'data' => $options['colors_initial'] ?? '',
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo produit',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image (JPEG, PNG, GIF ou WEBP).',
                    ]),
                ],
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'data' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportProduct::class,
            'colors_initial' => '',
        ]);
    }
}
