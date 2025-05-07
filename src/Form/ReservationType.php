<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'en_attente',
                    'Confirmée' => 'confirmee',
                    'Annulée' => 'annulee',
                ],
                'placeholder' => 'Choisir un statut',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('priceTotal', NumberType::class, [
                'label' => 'Prix total (€)',
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => 0.01,
                    'class' => 'form-control',
                ],
            ])
            ->add('modePaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Carte bancaire' => 'carte bancaire',
                    'PayPal' => 'paypal',
                    'Virement' => 'virement',
                ],
                'placeholder' => 'Choisir un mode de paiement',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('evenement', EntityType::class, [
                'label' => 'Événement',
                'class' => Evenement::class,
                'choice_label' => 'nom', // ou un autre champ comme 'titre'
                'placeholder' => 'Choisir un événement',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'submit_label' => 'Créer la réservation',
        ]);
    }
}