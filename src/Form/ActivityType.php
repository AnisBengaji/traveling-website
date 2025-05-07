<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Destination;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomActivity')
            
            ->add('type')
            ->add('description')
            ->add('activityPrice')
            ->add('imageActivity', FileType::class, [
                'label' => 'Primary Image (JPEG, PNG, GIF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or GIF)',
                    ])
                ],
            ])
            ->add('imageActivity2', FileType::class, [
                'label' => 'Image 2 (JPEG, PNG, GIF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or GIF)',
                    ])
                ],
            ])
            ->add('imageActivity3', FileType::class, [
                'label' => 'Image 3 (JPEG, PNG, GIF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or GIF)',
                    ])
                ],
            ])
            ->add('dateActivite', null, [
                'widget' => 'single_text',
            ])
            ->add('iddestination', EntityType::class, [
                'class' => Destination::class,
                'choice_label' => function(Destination $destination){
                    return $destination->getPays(). ' - ' . $destination->getVille();
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}