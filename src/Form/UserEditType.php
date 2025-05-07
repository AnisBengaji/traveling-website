<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => true,
                'label' => 'Last Name',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Last name is required']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Last name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Last name cannot be longer than {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z\s-]+$/',
                        'message' => 'Last name can only contain letters, spaces and hyphens'
                    ])
                ]
            ])
            ->add('prenom', TextType::class, [
                'required' => true,
                'label' => 'First Name',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'First name is required']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'First name must be at least {{ limit }} characters long',
                        'maxMessage' => 'First name cannot be longer than {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z\s-]+$/',
                        'message' => 'First name can only contain letters, spaces and hyphens'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email is required']),
                    new Assert\Email(['message' => 'Please enter a valid email address']),
                    new Assert\Length([
                        'max' => 180,
                        'maxMessage' => 'Email cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('numTel', TelType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Phone number is required']),
                    new Assert\Length([
                        'min' => 8,
                        'max' => 15,
                        'minMessage' => 'Phone number must be at least {{ limit }} digits',
                        'maxMessage' => 'Phone number cannot be longer than {{ limit }} digits'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'Phone number can only contain digits'
                    ])
                ]
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Admin' => 'admin',
                    'Client' => 'client',
                    'Fournisseur' => 'fournisseur',
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Role is required']),
                    new Assert\Choice([
                        'choices' => ['admin', 'client', 'fournisseur'],
                        'message' => 'Please select a valid role'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
