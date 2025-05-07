<?php

namespace App\Form;

use App\Entity\NotificationPreference;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationPreferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('emailEnabled', CheckboxType::class, [
                'label' => 'Recevoir les notifications par email',
                'required' => false,
            ])
            ->add('smsEnabled', CheckboxType::class, [
                'label' => 'Recevoir les notifications par SMS',
                'required' => false,
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => '+33 6 12 34 56 78'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NotificationPreference::class,
        ]);
    }
} 