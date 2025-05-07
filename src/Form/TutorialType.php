<?php
// src/Form/TutorialType.php
namespace App\Form;

use App\Entity\Tutorial;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Offre;

class TutorialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_tutorial', TextType::class, [
                'label' => 'Nom du tutoriel',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom du tutoriel',
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d')
                ],
                'html5' => true,
                'label' => 'Date de début',
                'required' => true,
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text', // Affiche un champ de saisie de date unique
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('prix_tutorial', NumberType::class, [
                'label' => 'Prix',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prix du tutoriel',
                ],
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez l\'URL du tutoriel',
                ],
            ])
            ->add('offre', EntityType::class, [
                'class' => Offre::class,
                'choice_label' => 'titre',
                'label' => 'Offre associée',
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tutorial::class, // Associe ce formulaire à l'entité Tutorial
        ]);
    }
}