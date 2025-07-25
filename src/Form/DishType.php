<?php

namespace App\Form;

use App\Entity\Dish;
use App\Enum\DishCategoryEnum;
use App\Enum\DishTypeEnum;
use App\Form\DataTransformer\StringToFileTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DishType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Give your product a name (min. 3, max. 50 characters)*',
                'required' => true,
                'constraints' => [
                    new Length(null, 3, 50)
                ]
            ])
            ->add('description', TextType::class, [
                'label' => 'Share some words about it (max 250 characters)',
                'constraints' => [
                    new Length(null, null, 250)
                ]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Set a compative price*',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Positive(),
                    new Length(null, 1, 7),
                    new Range(null, null, null, null, null, null, 0.01, null, 9999.99)
                ]
            ])
            ->add('img', FileType::class, [
                'label' => 'Choose an tempting image',
                'required' => true,
                'constraints' => [
                    new File(null, '2M'),
                    new Image()
                ]
            ])
            ->add('category', EnumType::class, [
                'label' => 'Select the category the dish belongs to*',
                'class' => DishCategoryEnum::class,
                'required' => true,
                'choice_label' => fn(?DishCategoryEnum $category) => $category ? $category->value : null,
                'choice_value' => fn(?DishCategoryEnum $category) => $category ? $category->name : null,
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            ->add('type', EnumType::class, [
                'label' => 'Select the type of the food, like it is a salad, pasta, etc...*',
                'class' => DishTypeEnum::class,
                'required' => true,
                'choice_label' => fn(?DishTypeEnum $type) => $type ? $type->value : null,
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            // ->add('sizes', ChoiceType::class, [
            //     'label' => 'Select the different portion sizes you offer for this dish (multiple selection)',
            //     'choices' => [
            //         'XS' => 'XS',
            //         'Small' => 'Small',
            //         'Medium' => 'Medium',
            //         'Large' => 'Large',
            //         'XL' => 'XL',
            //         'XXL' => 'XXL'
            //     ],
            //     'attr' => [
            //         'style' => 'border: 1px solid lightgray; padding: 5px;'
            //     ],
            //     'multiple' => true,
            //     'expanded' => true,
            // ])
            ->add('sizes', CollectionType::class, [
                'entry_options' => [
                    'constraints' => [
                        new NotBlank(),
                        new PositiveOrZero(),
                        new Length(min: 1, max: 5),
                        new Range(min: 0.0, max: 99.9),
                        new Callback(function(mixed $value, ExecutionContextInterface $context) {
                            if ($value > 0.0 && $value < 0.01) {
                                $context->addViolation(
                                    'The value must be equal or greater than 0.01 if not zero.'
                                );
                            }
                        }),
                    ]
                ],
            ])
            ->add('extras', TextType::class, [
                'row_attr' => [
                    'style' => 'display: none;'
                ],
                'mapped' => false,
            ])
            //->get('extras')->addModelTransformer(new JsonZipsToZipsEntityTransformer($this->em, $builder->getData()))
            ->get('img')->addModelTransformer(new StringToFileTransformer())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dish::class,
        ]);
    }
}
