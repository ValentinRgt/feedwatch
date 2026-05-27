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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SourceType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
                    new Assert\NotBlank(normalizer: 'trim'),
                ],
            ])
            ->add('url', UrlType::class, [
                'default_protocol' => 'https',
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
                    new Assert\NotBlank(normalizer: 'trim'),
                ],
            ])
            ->add('format', EnumType::class, [
                'class' => FormatEnum::class,
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5',
                    'data-format-toggle-target' => 'select',
                    'data-action' => 'change->format-toggle#toggle',
                ]
            ])
            ->add('itemContainer', TextType::class, [
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5',
                    'placeholder' => 'pages.admin.sources.form.html_scraping.item_container_placeholder',
                ]
            ])
            ->add('itemTitle', TextType::class, [
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5',
                    'placeholder' => 'pages.admin.sources.form.html_scraping.item_title_placeholder',
                ]
            ])
            ->add('itemLink', TextType::class, [
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5',
                    'placeholder' => 'pages.admin.sources.form.html_scraping.item_link_placeholder',
                ]
            ])
            ->add('itemPublishedAt', TextType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'block mb-2 text-md font-medium text-gray-900'
                ],
                'attr' => [
                    'data-required' => 'false',
                    'class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500'
                    . 'focus:border-blue-500 block w-full p-2.5',
                    'placeholder' => 'pages.admin.sources.form.html_scraping.item_published_at_placeholder',
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
                'choices' => array_filter(
                    StatusEnum::cases(),
                    fn (StatusEnum $case): bool => $case !== StatusEnum::IN_ERROR
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

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!is_array($data) || ($data['format'] ?? null) !== FormatEnum::HTML->value) {
                return;
            }

            foreach (['itemContainer', 'itemTitle', 'itemLink'] as $field) {
                $options = $form->get($field)->getConfig()->getOptions();
                $options['constraints'] = [
                    new Assert\NotBlank(normalizer: 'trim'),
                ];

                $form->add($field, TextType::class, $options);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Source::class,
            'attr' => [
                'class' => 'grid gap-6 mb-6 md:grid-cols-2',
                'data-controller' => 'format-toggle',
                'data-format-toggle-match-value' => FormatEnum::HTML->value,
            ]
        ]);
    }
}
