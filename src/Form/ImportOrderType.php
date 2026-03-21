<?php

namespace App\Form;

use App\Entity\ImportOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class ImportOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Numéro de téléphone mobile',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('deliveryAddress', TextareaType::class, [
                'label' => 'Lieu de livraison / adresse',
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => ImportOrderItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Produits',
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'MVola' => 'mvola',
                    'Orange Money' => 'orange_money',
                    'Airtel Money' => 'airtel_money',
                    'Paiement en espèces dans notre local' => 'cash',
                ],
                'attr' => ['class' => 'form-control form-select'],
            ])
            ->add('paymentReference', TextType::class, [
                'label' => 'Référence du paiement',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('cgvAccepted', CheckboxType::class, [
                'label' => 'J\'accepte les conditions générales de vente',
                'mapped' => true,
                'constraints' => [new IsTrue(['message' => 'Vous devez accepter les conditions générales de vente.'])],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportOrder::class,
        ]);
    }
}
