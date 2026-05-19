<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
                'label' => 'pages.admin.categories.form.name',
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5',
                    'placeholder' => 'pages.admin.categories.form.name_placeholder'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'normalizer' => 'trim',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'attr' => [
                'class' => 'space-y-5',
            ],
        ]);
    }
}
