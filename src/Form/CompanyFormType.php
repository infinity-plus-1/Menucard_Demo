<?php

namespace App\Form;

use App\Entity\Company;
use App\Enum\CuisinesEnum;
use App\Form\DataTransformer\JsonZipsToZipsEntityTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CompanyFormType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                    'label' => 'Company name*',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(min: 3)
                    ]
            ])
            ->add('type', EnumType::class, [
                'label' => 'Cuisine*',
                'required' => true,
                'class' => CuisinesEnum::class
            ])
            ->add(
                'zip', TextType::class, [
                    'label' => 'Zipcode*',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(min: 3)
                    ]
            ])
            ->add('city', TextType::class, [
                'label' => 'City*',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 3)
                ]
            ])
            ->add('street', TextType::class, [
                'label' => 'Street*',
                'required' => true
            ])
            ->add('sn', NumberType::class, [
                'label' => 'Street number*',
                'required' => true,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('phone', NumberType::class, [
                'label' => 'Telephone number*',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 4)
                ]
            ])
            ->add('email', TextType::class, [
                'label' => 'E-Mail',
                'constraints' => [
                    new NotBlank(),
                    new Email()
                ]
            ])
            ->add('website', TextType::class, [
                'label' => 'Website',
                'constraints' => [
                    new Length(min: 4)
                ]
            ])
            ->add('tax', TextType::class, [
                'label' => 'Tax number*',
                'required' => true
            ])
            ->add('logo', FileType::class, [
                'label' => 'Upload your logo',
                'mapped' => false
            ])
            ->add('deliveryZips', TextType::class, [
                'required' => true,
                'row_attr' => [
                    'style' => 'display: none;'
                ],
                'mapped' => true,
            ])
            ->get('deliveryZips')->addModelTransformer(new JsonZipsToZipsEntityTransformer($this->em, $builder->getData()))
        ;
    }

    

    // public function configureOptions(OptionsResolver $resolver)
    // {
    //     $resolver->setDefaults([
    //         'data_class' => Company::class,
    //     ]);
    // }
}

