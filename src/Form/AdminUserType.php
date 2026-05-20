<?php

namespace App\Form;

use App\Entity\Service;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'El. paštas',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rolė',
                'choices' => [
                    'Vartotojas' => 'ROLE_USER',
                    'Darbuotojas' => 'ROLE_EMPLOYEE',
                    'Administratorius' => 'ROLE_ADMIN',
                ],
                'expanded' => false,
                'multiple' => false,
                'mapped' => false,
                'data' => $this->getPrimaryRole($options['data']),
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'name',
                'label' => 'Priskirtas servisas',
                'placeholder' => 'Pasirinkite servisą',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    private function getPrimaryRole(?User $user): ?string
    {
        if (!$user) {
            return 'ROLE_USER';
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'ROLE_ADMIN';
        }

        if (in_array('ROLE_EMPLOYEE', $roles, true)) {
            return 'ROLE_EMPLOYEE';
        }

        return 'ROLE_USER';
    }
}