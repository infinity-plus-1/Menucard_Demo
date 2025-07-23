<?php

namespace App\Form;

use App\Entity\User;
use App\Form\DataTransformer\SingleRoleToArrayTransformer;
use App\Validator\PasswordValidator;
use App\Validator\ValidRole;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction('/registration')
            ->add('email', EmailType::class, ['label' => 'E-Mail:', 'required' => true, 'attr' => ['data-register-target' => 'email']])
            ->add('forename', TextType::class, ['label' => 'Firstname:', 'required' => true, 'attr' => ['data-register-target' => 'firstname']])
            ->add('surname', TextType::class, ['label' => 'Lastname:', 'required' => true, 'attr' => ['data-register-target' => 'lastname']])
            ->add('street', TextType::class, ['label' => 'Street:', 'required' => true, 'attr' => ['data-register-target' => 'street']])
            ->add('sn', TextType::class, ['label' => 'Street number:', 'required' => true, 'attr' => ['data-register-target' => 'sn']])
            ->add('zipcode', TextType::class, ['label' => 'Zip:', 'required' => true, 'attr' => ['data-register-target' => 'zip']])
            ->add('city', TextType::class, ['label' => 'City:', 'required' => true, 'attr' => ['data-register-target' => 'city']])
            ->add('roles', ChoiceType::class,
                    [
                    'label' => 'Account type:',
                    'required' => true,
                    'placeholder' => 'Choose account type',
                    'choices' => [
                        'Consumer (I want to eat)' => 'ROLE_CONSUMER',
                        'Company (I want to cook)' => 'ROLE_COMPANY'
                    ],
                    'constraints' => [
                        new ValidRole()
                    ],
                    'attr' => ['data-register-target' => 'at']
                ]
            )
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => ['label' => 'Password:', 'hash_property_path' => 'password'],
                'second_options' => ['label' => 'Repeat password:'],
                'mapped' => false,
                'constraints' => [
                    new PasswordValidator(),
                ],
                'attr' => ['data-register-target' => 'pw']
            ])
            ->add('cancelButton', ButtonType::class, [
                'attr' => [
                    'class' => 'btn btn-secondary',
                    'data-bs-dismiss' => 'modal',
                    'data-form-modal-target' => 'cancelBtn'
                ]
            ])
            ->add('submitButton', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                    'data-form-modal-target' => 'confirmBtn',
                    'data-register-target' => 'submitButton'
                ]
            ])
            ->get('roles')
            ->addModelTransformer(new SingleRoleToArrayTransformer())
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
