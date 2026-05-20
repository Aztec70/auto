<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'El. paštas',
                'constraints' => [
                    new NotBlank(
                        message: 'Įveskite el. pašto adresą.',
                    ),
                    new Email(
                        message: 'El. pašto formatas nėra tinkamas.',
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Sutinku su sąlygomis',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Turite sutikti su sąlygomis.',
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Slaptažodis',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Įveskite slaptažodį.',
                    ),
                    new Length(
                        min: 6,
                        max: 50,
                        minMessage: 'Slaptažodis turi būti bent {{ limit }} simbolių ilgio.',
                        maxMessage: 'Slaptažodis negali būti ilgesnis nei {{ limit }} simbolių.',
                    ),
                    new Regex(
                        pattern: '/[A-ZĄČĘĖĮŠŲŪŽ]/u',
                        message: 'Slaptažodyje turi būti bent viena didžioji raidė.',
                    ),
                    new Regex(
                        pattern: '/\d/',
                        message: 'Slaptažodyje turi būti bent vienas skaičius.',
                    ),
                    new Regex(
                        pattern: '/[^A-Za-zĄČĘĖĮŠŲŪŽąčęėįšųūž0-9]/u',
                        message: 'Slaptažodyje turi būti bent vienas specialus simbolis.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}