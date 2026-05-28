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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Įveskite serviso pavadinimą.'),
                    new Assert\Length(
                        min: 3,
                        max: 100,
                        minMessage: 'Serviso pavadinimas turi būti bent {{ limit }} simbolių ilgio.',
                        maxMessage: 'Serviso pavadinimas negali būti ilgesnis nei {{ limit }} simbolių.'
                    ),
                    new Assert\Regex(
                        pattern: '/^[\p{L}\p{N}\s\-\.\,&]+$/u',
                        message: 'Serviso pavadinime galima naudoti raides, skaičius, tarpus ir įprastus skyrybos ženklus.'
                    ),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresas',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Įveskite serviso adresą.'),
                    new Assert\Length(
                        min: 5,
                        max: 150,
                        minMessage: 'Adresas turi būti bent {{ limit }} simbolių ilgio.',
                        maxMessage: 'Adresas negali būti ilgesnis nei {{ limit }} simbolių.'
                    ),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefonas',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Įveskite telefono numerį.'),
                    new Assert\Regex(
                        pattern: '/^(?:\+3706\d{7}|06\d{7})$/',
                        message: 'Įveskite lietuvišką telefono numerį formatu +3706xxxxxxx arba 06xxxxxxx.'
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'El. paštas',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Įveskite el. pašto adresą.'),
                    new Assert\Email(message: 'El. pašto formatas nėra tinkamas.'),
                    new Assert\Length(
                        max: 180,
                        maxMessage: 'El. pašto adresas negali būti ilgesnis nei {{ limit }} simbolių.'
                    ),
                ],
            ])
            ->add('workDayFrom', ChoiceType::class, [
                'label' => 'Darbo dienos nuo',
                'choices' => $dayChoices,
                'placeholder' => 'Pasirinkite dieną',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Pasirinkite darbo pradžios dieną.'),
                ],
            ])
            ->add('workDayTo', ChoiceType::class, [
                'label' => 'Darbo dienos iki',
                'choices' => $dayChoices,
                'placeholder' => 'Pasirinkite dieną',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Pasirinkite darbo pabaigos dieną.'),
                ],
            ])
            ->add('workTimeFrom', TimeType::class, [
                'label' => 'Dirba nuo',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
                'html5' => true,
                'with_minutes' => true,
                'with_seconds' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Pasirinkite darbo pradžios laiką.'),
                ],
                'attr' => [
                    'step' => 1800,
                ],
            ])
            ->add('workTimeTo', TimeType::class, [
                'label' => 'Dirba iki',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
                'html5' => true,
                'with_minutes' => true,
                'with_seconds' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Pasirinkite darbo pabaigos laiką.'),
                ],
                'attr' => [
                    'step' => 1800,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Aprašymas',
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        max: 1000,
                        maxMessage: 'Aprašymas negali būti ilgesnis nei {{ limit }} simbolių.'
                    ),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Serviso nuotrauka',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '4M',
                        maxSizeMessage: 'Nuotrauka negali būti didesnė nei 4 MB.',
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
                'required' => true,
                'constraints' => [
                    new Assert\Count(
                        min: 1,
                        minMessage: 'Pasirinkite bent vieną kategoriją.'
                    ),
                ],
                'attr' => [
                    'class' => 'tom-select',
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $service = $event->getData();

            if (!$service instanceof Service) {
                return;
            }

            $workDayFrom = $service->getWorkDayFrom();
            $workDayTo = $service->getWorkDayTo();
            $workTimeFrom = $service->getWorkTimeFrom();
            $workTimeTo = $service->getWorkTimeTo();

            if ($workDayFrom !== null && $workDayTo !== null) {
                if ($workDayFrom === $workDayTo) {
                    $form->get('workDayTo')->addError(
                        new FormError('Darbo pabaigos diena negali būti tokia pati kaip pradžios diena.')
                    );
                }

                if ($workDayTo < $workDayFrom) {
                    $form->get('workDayTo')->addError(
                        new FormError('Darbo pabaigos diena negali būti ankstesnė už pradžios dieną.')
                    );
                }
            }

            if ($workTimeFrom !== null && $workTimeTo !== null) {
                if ($workTimeTo <= $workTimeFrom) {
                    $form->get('workTimeTo')->addError(
                        new FormError('Darbo pabaigos laikas turi būti vėlesnis už pradžios laiką.')
                    );
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}