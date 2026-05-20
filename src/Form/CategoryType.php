<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Kategorijos pavadinimas',
            'attr' => [
                'placeholder' => 'Pvz. Variklio remontas',
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'Įveskite kategorijos pavadinimą.'),
                new Assert\Length(
                    min: 2,
                    max: 255,
                    minMessage: 'Pavadinimas turi būti bent 2 simbolių ilgio.',
                    maxMessage: 'Pavadinimas negali būti ilgesnis nei 255 simboliai.'
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}