<?php

namespace App\Form;

use App\Entity\ImportOrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ImportOrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productCode', TextType::class, [
                'label' => false,
                'attr' => ['class' => 'form-control item-product-code', 'placeholder' => 'Code produit'],
            ])
            ->add('color', TextType::class, [
                'label' => false,
                'attr' => ['class' => 'form-control item-color', 'placeholder' => 'Couleur'],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => false,
                'data' => 1,
                'attr' => ['class' => 'form-control item-quantity', 'min' => 1],
                'constraints' => [new Positive(message: 'La quantité doit être au moins 1')],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportOrderItem::class,
        ]);
    }
}
