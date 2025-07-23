<?php

namespace App\Form;

use App\Validator\PasswordValidator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class UpdatePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction('update_password')
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => [
                    'label' => 'Password*:',
                    'attr' => [
                        'placeholder' => 'Enter your new password here'
                    ]
                ],
                'second_options' => [
                    'label' => 'Repeat password*:',
                    'attr' => [
                        'placeholder' => 'Repeat the same new password here'
                    ]
                ],
                'mapped' => true,
                'constraints' => [
                    new PasswordValidator(),
                ],
            ])
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Enter your old password to submit changes*:',
                'mapped' => true,
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
            ->add('submitButton', SubmitType::class, [
                'label' => 'Submit',
                'attr' => [
                    'class' => 'btn btn-primary',
                ]
            ])
        ;
    }
}