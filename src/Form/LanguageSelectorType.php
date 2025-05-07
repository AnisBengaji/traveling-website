<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageSelectorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locale', ChoiceType::class, [
                'choices' => [
                    'language.en' => 'en',
                    'language.fr' => 'fr',
                    'language.es' => 'es',
                ],
                'choice_translation_domain' => 'messages',
                'data' => $options['current_locale'],
                'attr' => [
                    'class' => 'form-control rounded-pill',
                    'id' => 'language-select',
                ],
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'current_locale' => 'en',
        ]);
    }
}