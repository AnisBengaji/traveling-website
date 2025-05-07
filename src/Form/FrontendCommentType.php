<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FrontendCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Add a comment...',
                    'class' => 'auto-resize',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Comment cannot be empty']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 500,
                        'minMessage' => 'Comment must be at least {{ limit }} characters',
                        'maxMessage' => 'Comment cannot exceed {{ limit }} characters',
                    ]),
                ],
            ]);
    }}