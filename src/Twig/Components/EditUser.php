<?php

namespace App\Twig\Components;

use App\Dto\EditUserDto;
use App\Entity\User;
use App\Form\UserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('EditUser')]
final class EditUser extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable:true)]
    public ?EditUserDto $user = NULL;

    #[LiveProp(writable:true)]
    public ?string $oldPassword = NULL;

    #[LiveProp]
    public ?string $message = NULL;

    #[LiveProp]
    public ?int $status = NULL;

    #[LiveProp]
    public array $errors = [];

    protected function instantiateForm(): FormInterface
    {
        $this->user = $this->user ?? new EditUserDto();
        $form = $this->createForm(UserFormType::class, $this->user);
        $form->get('oldPassword')->setData($this->oldPassword);
        return $form;
    }

    #[LiveAction]
    public function save(ObjectMapperInterface $omi, UserPasswordHasherInterface $uphi, EntityManagerInterface $em): void
    {
        $this->errors = [];
        $form = $this->getForm();
        $oldPassword = $this->formValues['oldPassword'] ?? '';
        $form->submit($this->formValues);
        $formErrors = $form->getErrors(true);

        foreach ($formErrors as $error) {
            if ($error instanceof FormError) {
                if ($error->getCause() instanceof ConstraintViolation) {
                    $this->errors[] = $error->getMessage();
                }
            }
        }

        $this->resetForm();

        $this->formValues['oldPassword'] = $oldPassword;

        try {
            $this->submitForm();
        } catch (\Throwable $th) {
            //Ignore the catch, we will provide errors ourself.
        }
        

        if (count($this->errors) > 0) {
            $this->status = 400;
            $this->message = 'One or more errors occured.';
            return;
        }

        $user = $this->getUser();

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            $this->status = 401;
            $this->message = 'You need to be logged in.';
            return;
        }

        if (!$uphi->isPasswordValid($user, $oldPassword)) {
            $this->message = 'The current password you have entered is invalid.';
            $this->status = 400;
            return;
        }

        $omi->map($this->user, $user);

        $em->persist($user);
        $em->flush();


        $this->status = 200;
        $this->message = 'Data have been updated. The form will reload in 5 seconds.';
    }
}
