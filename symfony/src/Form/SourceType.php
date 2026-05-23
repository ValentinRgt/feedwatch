<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Source;
use App\Enum\FormatEnum;
use App\Enum\PeriodicityEnum;
use App\Enum\StatusEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SourceType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Source $source */
        $source = $builder->getData();
        if ($source instanceof Source && $source->getStatus() === StatusEnum::IN_ERROR) {
            $source->setStatus(StatusEnum::INACTIVE);
        }

        $builder
            ->add('name', TextType::class, [
                'row_attr' => [
                    'class' => 'col-span-2'
                ],
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'normalizer' => 'trim',
                    ]),
                ],
            ])
            ->add('url', UrlType::class, [
                'row_attr' => [
                    'class' => 'col-span-2'
                ],
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'normalizer' => 'trim',
                    ]),
                ],
            ])
            ->add('format', EnumType::class, [
                'class' => FormatEnum::class,
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5'
                ]
            ])
            ->add('status', EnumType::class, [
                'class' => StatusEnum::class,
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5'
                ],
                'choices' => array_values(
                    array_filter(
                        StatusEnum::cases(),
                        fn (StatusEnum $case): bool => $case !== StatusEnum::IN_ERROR
                    )
                ),
            ])
            ->add('periodicity', EnumType::class, [
                'class' => PeriodicityEnum::class,
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5'
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'empty_data' => null,
                'required' => false,
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Source::class,
            'attr' => [
                'class' => 'grid gap-6 mb-6 md:grid-cols-2'
            ]
        ]);
    }
}
