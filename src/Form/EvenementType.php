<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('Date_EvenementDepart', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'input' => 'datetime', // Assurer que la valeur est bien de type DateTime
                'required' => true, // S'assurer que le champ est requis
            ])
            ->add('Date_EvenementArriver', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'input' => 'datetime', // Assurer que la valeur est bien de type DateTime
                'required' => true, // S'assurer que le champ est requis
            ])        
            ->add('lieu', TextType::class)
            ->add('Description', TextareaType::class)
            ->add('price', MoneyType::class)
            ->add('latitude', NumberType::class)
            ->add('longitude', NumberType::class)
            ->add('imageFile', FileType::class, [
                'label' => 'Image de l\'événement',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (jpg, png, webp)',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}