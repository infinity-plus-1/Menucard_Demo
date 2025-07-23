<?php

namespace App\Form;

use App\Entity\User;
use App\Form\DataTransformer\SingleRoleToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction('/edit_user')
            ->add (
                'email',
                EmailType::class,
                [
                    'label' => 'E-Mail*:',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 6,
                            max: 180,
                            minMessage: 'Your email address must be at least {{ limit }} characters long',
                            maxMessage: 'Your email address cannot be longer than {{ limit }} characters',
                        ),
                    ],
                ]
            )
            ->add (
                'forename',
                TextType::class,
                [
                    'label' => 'Firstname*:',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 2,
                            max: 50,
                            minMessage: 'Your first name must be at least {{ limit }} characters long',
                            maxMessage: 'Your first name cannot be longer than {{ limit }} characters',
                        ),
                    ]
                ]
            )
            ->add (
                'surname',
                TextType::class,
                [
                    'label' => 'Surname*:',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 2,
                            max: 50,
                            minMessage: 'Your surname must be at least {{ limit }} characters long',
                            maxMessage: 'Your surname cannot be longer than {{ limit }} characters',
                        ),
                    ]
                ]
            )
            ->add (
                'street',
                TextType::class,
                [
                    'label' => 'Street*:',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 2,
                            max: 60,
                            minMessage: 'Your street name be at least {{ limit }} characters long',
                            maxMessage: 'Your street name cannot be longer than {{ limit }} characters',
                        ),
                    ]
                ]
            )
            ->add (
                'sn',
                TextType::class,
                [
                    'label' => 'Street number*:',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 1,
                            max: 4,
                            minMessage: 'Your street number be at least {{ limit }} characters long',
                            maxMessage: 'Your street number cannot be longer than {{ limit }} characters',
                        ),
                    ]
                ]
            )
            ->add (
                'zipcode',
                TextType::class,
                [
                    'label' => 'Zip*:',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 2,
                            max: 10,
                            minMessage: 'Your zipcode be at least {{ limit }} characters long',
                            maxMessage: 'Your zipcode cannot be longer than {{ limit }} characters',
                        ),
                    ]
                ]
            )
            ->add (
                'city',
                TextType::class,
                [
                    'label' => 'City*:',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 2,
                            max: 60,
                            minMessage: 'Your city be at least {{ limit }} characters long',
                            maxMessage: 'Your city cannot be longer than {{ limit }} characters',
                        ),
                    ]
                ]
            )
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Enter your old password to submit changes*:',
                'mapped' => false,
                'required' => true,
                'always_empty' => false,
                'attr' => [
                    'placeholder' => 'Old password'
                ],
                'constraints' => [
                    new UserPassword([
                        'message' => 'The password you have entered does not match.'
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'csrf_protection' => true,
                'csrf_field_name' => '_token',
                'csrf_token_id'   => 'edit_user',
            ]
        );
    }
}