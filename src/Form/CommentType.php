<?php

namespace App\Form;

use App\Entity\Comment;
use App\Entity\Publication;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Comment Content',
                'attr' => ['rows' => 5],
                'required' => true,
            ])
            ->add('publication', EntityType::class, [
                'class' => Publication::class,
                'choice_label' => 'title',
                'label' => 'Publication',
                'placeholder' => 'Select a publication',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'attr' => ['novalidate' => 'novalidate'], // Disable HTML5 validation
        ]);
    }
}