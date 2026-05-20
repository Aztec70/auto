<?php

namespace App\Form;

use App\Entity\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Remonto aprašymas',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Įrašykite bendrą remonto aprašymą',
                ],
            ])
            ->add('workPerformed', TextareaType::class, [
                'label' => 'Atlikti darbai',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Įrašykite, kokie darbai buvo atlikti',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Kaina',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => 'Pvz. 120.00',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}