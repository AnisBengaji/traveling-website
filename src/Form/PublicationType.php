<?php

namespace App\Form;

use App\Entity\Publication;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => ['placeholder' => 'Enter publication title'],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Content',
                'attr' => ['placeholder' => 'Enter publication content'],
            ])
            ->add('visibility', ChoiceType::class, [
                'label' => 'Visibility',
                'choices' => [
                    'Public' => 'public',
                    'Private' => 'private',
                ],
                'attr' => ['placeholder' => 'Select visibility'],
            ])
            ->add('category', EntityType::class, [
                'label' => 'Category',
                'class' => Category::class,
                'choice_label' => 'nom_category',
                'placeholder' => 'Select a category',
            ])
            ->add('imageSource', ChoiceType::class, [
                'label' => 'Image Source',
                'choices' => [
                    'None' => null,
                    'Upload File' => 'file',
                    'URL' => 'url',
                ],
                'placeholder' => 'Select image source',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image File (PNG, JPEG)',
                'required' => false,
                'mapped' => false,
            ])
            ->add('imageUrl', TextType::class, [
                'label' => 'Image URL',
                'required' => false,
                'mapped' => false,
                'attr' => ['placeholder' => 'Enter image URL'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
        ]);
    }
}