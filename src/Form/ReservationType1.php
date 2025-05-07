<?php
namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ReservationType1 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $evenement = $options['evenement'];
        $user = $options['user']; // Accéder à l'utilisateur passé dans les options

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
            ->add('modePaiement', ChoiceType::class, [
                'choices' => [
                    'Carte bancaire' => 'carte bancaire',
                    'Virement' => 'virement',
                    'PayPal' => 'paypal',
                ],
                'placeholder' => 'Choisir le mode de paiement',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('walletPoints', NumberType::class, [
                'label' => 'Points Wallet à utiliser',
                'required' => false,
                'mapped' => false, // Non mappé à une propriété de l'entité
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Entrez le nombre de points',
                ],
                'constraints' => [
                    new Callback(function ($value, ExecutionContextInterface $context) use ($user, $evenement) {
                        if ($value === null || $value <= 0) {
                            return; // Pas de validation si aucun point n'est saisi
                        }

                        $wallet = $user ? $user->getWallet() : null;
                        $score = $wallet ? $wallet->getScore() : 0;
                        $price = $evenement ? $evenement->getPrice() : 0;

                        if ($value > $score) {
                            $context->buildViolation('Vous ne pouvez pas utiliser plus de points que votre score disponible ({{ score }} points).')
                                ->setParameter('{{ score }}', $score)
                                ->addViolation();
                        }

                        if ($value > $price) {
                            $context->buildViolation('Vous ne pouvez pas utiliser plus de points que le prix de l\'événement ({{ price }} €).')
                                ->setParameter('{{ price }}', $price)
                                ->addViolation();
                        }
                    }),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'evenement' => null,
            'user' => null, // Nouvelle option pour passer l'utilisateur
        ]);

        $resolver->setRequired(['evenement', 'user']); // Rendre les options obligatoires
    }
}