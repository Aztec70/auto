<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Category;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Remonto kategorija',
                'placeholder' => 'Pasirinkite kategoriją',
                'mapped' => false,
                'choice_attr' => function (Category $category) {
                    $serviceIds = [];

                    foreach ($category->getServices() as $service) {
                        $serviceIds[] = $service->getId();
                    }

                    return [
                        'data-services' => implode(',', $serviceIds),
                    ];
                },
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'name',
                'label' => 'Servisas',
                'placeholder' => 'Pasirinkite servisą',
                'choice_attr' => function (Service $service) {
                    $categoryIds = [];

                    foreach ($service->getCategories() as $category) {
                        $categoryIds[] = $category->getId();
                    }

                    $workDayFrom = $service->getWorkDayFrom();
                    $workDayTo = $service->getWorkDayTo();
                    $workTimeFrom = $service->getWorkTimeFrom();
                    $workTimeTo = $service->getWorkTimeTo();

                    return [
                        'data-categories' => implode(',', $categoryIds),
                        'data-work-day-from' => $workDayFrom ?? '',
                        'data-work-day-to' => $workDayTo ?? '',
                        'data-work-time-from' => $workTimeFrom ? $workTimeFrom->format('H:i') : '',
                        'data-work-time-to' => $workTimeTo ? $workTimeTo->format('H:i') : '',
                    ];
                },
            ])
            ->add('carBrand', TextType::class, [
                'label' => 'Automobilio markė',
                'attr' => [
                    'placeholder' => 'Pvz. Audi',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Įveskite automobilio markę.'
                    ),
                    new Assert\Length(
                        min: 2,
                        minMessage: 'Automobilio markė turi būti bent 2 simbolių ilgio.',
                        max: 100,
                        maxMessage: 'Automobilio markė negali būti ilgesnė nei 100 simbolių.',
                    ),
                ],
            ])
            ->add('carModel', TextType::class, [
                'label' => 'Automobilio modelis',
                'attr' => [
                    'placeholder' => 'Pvz. A4',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Įveskite automobilio modelį.'
                    ),
                    new Assert\Length(
                        min: 1,
                        minMessage: 'Automobilio modelis turi būti bent 1 simbolio ilgio.',
                        max: 100,
                        maxMessage: 'Automobilio modelis negali būti ilgesnis nei 100 simbolių.',
                    ),
                ],
            ])
            ->add('carYear', IntegerType::class, [
                'label' => 'Pagaminimo metai',
                'attr' => [
                    'placeholder' => 'Pvz. 2012',
                    'min' => 1950,
                    'max' => date('Y') + 1,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Įveskite automobilio pagaminimo metus.'
                    ),
                    new Assert\Range(
                        min: 1950,
                        max: (int) date('Y') + 1,
                        notInRangeMessage: 'Pagaminimo metai turi būti tarp {{ min }} ir {{ max }}.',
                    ),
                ],
            ])
            ->add('visitDate', TextType::class, [
                'label' => 'Vizito data ir laikas',
                'attr' => [
                    'class' => 'form-control js-flatpickr',
                    'autocomplete' => 'off',
                    'placeholder' => 'Pasirinkite datą ir laiką',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Pasirinkite vizito datą ir laiką.'
                    ),
                    new Assert\Callback(function ($value, ExecutionContextInterface $context) {
                        if (!$value) {
                            return;
                        }

                        $root = $context->getRoot();
                        if (!$root instanceof FormInterface) {
                            return;
                        }

                        $service = $root->get('service')->getData();

                        if (!$service instanceof Service) {
                            $context->buildViolation('Pasirinkite servisą.')
                                ->addViolation();
                            return;
                        }

                        if ($value instanceof \DateTimeInterface) {
                            $date = \DateTime::createFromInterface($value);
                            $date->setTimezone(new \DateTimeZone('Europe/Vilnius'));
                        } elseif (is_string($value)) {
                            $date = \DateTime::createFromFormat('Y-m-d H:i', $value, new \DateTimeZone('Europe/Vilnius'));
                        } else {
                            $date = false;
                        }

                        if (!$date) {
                            $context->buildViolation('Neteisingas datos arba laiko formatas.')
                                ->addViolation();
                            return;
                        }

                        $now = new \DateTime('now', new \DateTimeZone('Europe/Vilnius'));

                        if ($date < $now) {
                            $context->buildViolation('Negalima pasirinkti praėjusios datos ar laiko.')
                                ->addViolation();
                            return;
                        }

                        $workDayFrom = $service->getWorkDayFrom();
                        $workDayTo = $service->getWorkDayTo();
                        $workTimeFrom = $service->getWorkTimeFrom();
                        $workTimeTo = $service->getWorkTimeTo();

                        if (
                            $workDayFrom === null ||
                            $workDayTo === null ||
                            $workTimeFrom === null ||
                            $workTimeTo === null
                        ) {
                            $context->buildViolation('Pasirinktas servisas neturi pilnai nurodyto darbo grafiko.')
                                ->addViolation();
                            return;
                        }

                        $selectedDay = (int) $date->format('N');

                        if ($selectedDay < $workDayFrom || $selectedDay > $workDayTo) {
                            $context->buildViolation('Pasirinktą dieną šis servisas nedirba.')
                                ->addViolation();
                            return;
                        }

                        $hour = (int) $date->format('H');
                        $minute = (int) $date->format('i');

                        if (!in_array($minute, [0, 30], true)) {
                            $context->buildViolation('Galima rinktis tik laiką kas 30 minučių.')
                                ->addViolation();
                            return;
                        }

                        $selectedMinutes = ($hour * 60) + $minute;
                        $workFromMinutes = ((int) $workTimeFrom->format('H') * 60) + (int) $workTimeFrom->format('i');
                        $workToMinutes = ((int) $workTimeTo->format('H') * 60) + (int) $workTimeTo->format('i');

                        if ($selectedMinutes < $workFromMinutes || $selectedMinutes > $workToMinutes) {
                            $context->buildViolation(sprintf(
                                'Galima rinktis tik darbo laiką nuo %s iki %s.',
                                $workTimeFrom->format('H:i'),
                                $workTimeTo->format('H:i')
                            ))->addViolation();
                        }
                    }),
                ],
            ])
            ->add('problemDescription', TextareaType::class, [
                'label' => 'Problemos aprašymas',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Trumpai aprašykite problemą',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Įrašykite problemos aprašymą.'
                    ),
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Problemos aprašymas turi būti bent 10 simbolių ilgio.',
                        max: 2000,
                        maxMessage: 'Problemos aprašymas negali būti ilgesnis nei 2000 simbolių.',
                    ),
                ],
            ]);

        $builder->get('visitDate')->addModelTransformer(new CallbackTransformer(
            function ($visitDate) {
                if ($visitDate instanceof \DateTimeInterface) {
                    return $visitDate->format('Y-m-d H:i');
                }

                return '';
            },
            function ($visitDateString) {
                if (!$visitDateString) {
                    return null;
                }

                if ($visitDateString instanceof \DateTimeInterface) {
                    return \DateTime::createFromInterface($visitDateString);
                }

                $date = \DateTime::createFromFormat('Y-m-d H:i', $visitDateString, new \DateTimeZone('Europe/Vilnius'));

                return $date ?: null;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
        ]);
    }
}