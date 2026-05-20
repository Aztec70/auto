<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dayChoices = [
            'Pirmadienis' => 1,
            'Antradienis' => 2,
            'Trečiadienis' => 3,
            'Ketvirtadienis' => 4,
            'Penktadienis' => 5,
            'Šeštadienis' => 6,
            'Sekmadienis' => 7,
        ];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Pavadinimas',
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresas',
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefonas',
            ])
            ->add('email', EmailType::class, [
                'label' => 'El. paštas',
            ])
            ->add('workDayFrom', ChoiceType::class, [
                'label' => 'Darbo dienos nuo',
                'choices' => $dayChoices,
                'placeholder' => 'Pasirinkite dieną',
                'required' => false,
            ])
            ->add('workDayTo', ChoiceType::class, [
                'label' => 'Darbo dienos iki',
                'choices' => $dayChoices,
                'placeholder' => 'Pasirinkite dieną',
                'required' => false,
            ])
            ->add('workTimeFrom', TimeType::class, [
                'label' => 'Dirba nuo',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'html5' => true,
                'with_minutes' => true,
                'with_seconds' => false,
                'attr' => [
                    'step' => 1800,
                ],
            ])
            ->add('workTimeTo', TimeType::class, [
                'label' => 'Dirba iki',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'html5' => true,
                'with_minutes' => true,
                'with_seconds' => false,
                'attr' => [
                    'step' => 1800,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Aprašymas',
                'required' => false,
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Serviso nuotrauka',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '4M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Įkelkite JPG, PNG arba WEBP formato nuotrauką.'
                    ),
                ],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Kategorijos',
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'tom-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}