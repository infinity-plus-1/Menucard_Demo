<?php

namespace App\Twig\Components;

use App\Entity\Company;
use App\Entity\User;
use App\Form\CompanyFormType;
use App\Utility\Paths;
use App\Utility\Utility;
use DirectoryIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('CompanyForm')]
final class CompanyForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    const SUCCESS = 1;
    const ERROR = 2;
    const OPEN = 3;

    #[LiveProp(writable:true)]
    public ?Company $data = NULL;

    #[LiveProp]
    public ?string $message = NULL;

    #[LiveProp]
    public ?int $status = NULL;

    public function isValidForm(): bool {
        $this->status = $this::OPEN;
        $this->message = '';
        return $this->getForm()->isSubmitted() && $this->getForm()->isValid();
    }

    protected function instantiateForm(): FormInterface
    {
        $this->status = $this::OPEN;
        $this->message = '';
        $user = $this->getUser();
        if ($this->data === NULL && $user instanceof User) {
            $this->data = $user->getCompany() ?? new Company();
        }
        return $this->createForm(CompanyFormType::class, $this->data);
    }

    private function _handleLogo(Request $request, Paths $path, EntityManagerInterface $em): bool
    {
        $file = NULL;

        $files = $request->files->all();

        if (is_array($files) && isset($files['company_form'])) {
            if (is_array($files['company_form']) && isset($files['company_form']['logo'])) {
                $file = $files['company_form']['logo'];
            }
        }

        $filesystem = new Filesystem();
        $dirPath = $path->getCompanyLogoDir($this->data->getId());
        if ($dirPath !== '' && !$filesystem->exists($dirPath)) {
            $filesystem->mkdir($dirPath);
        }
        if ($file instanceof UploadedFile && $file->isFile() && $dirPath !== '') {
            $fileMime = $file->getClientMimeType();
            if ($fileMime && $fileMime !== '') {
                $fileMime = explode('/', $fileMime);
                if (is_array($fileMime) && count($fileMime) > 1) {
                    if (trim($fileMime[0]) === 'image') {
                        $iterator = new DirectoryIterator($dirPath);
                        while ($iterator->valid()) {
                            if (!$iterator->isDot()) {
                                $filesystem->remove($iterator->getPathname());
                            }
                            $iterator->next();
                        }
                        $file->move($dirPath, 'logo.jpg');
                        $this->data->setLogo($path->getCompanyRelativeLogoDir($this->data->getId()) . '/logo.jpg');
                        $em->flush();
                    } else {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    #[LiveAction]
    public function save(Request $request, EntityManagerInterface $em, Paths $path): void {
        $this->status = $this::OPEN;
        $this->message = '';
        $this->submitForm();
        $form = $this->getForm();
        
        $repo = $em->getRepository(Company::class);
        $savedCompany = $this->data->getId()
            ? $repo->find($this->data->getId())
            : NULL
        ;
        $user = $this->getUser();

        if (!Utility::isCompanyAccount($user)) {
            $this->message = "This action is only available for commercial accounts.";
            $this->status = $this::ERROR;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user instanceof User) {
                if ($this->data->getId() === NULL) {
                    $em->persist($this->data);
                }
                $user->setCompany($this->data);
                $em->flush();

                if ($savedCompany && $savedCompany->getName() === $this->data->getName()) {
                    $this->message = "Company information have been updated. The page will reload in 5 seconds.";
                    $this->status = $this::SUCCESS;
                } elseif (!$savedCompany && $this->data->getId() !== NULL) {
                    $this->message = "Company has been registered successfuly. The page will reload in 5 seconds.";
                    $this->status = $this::SUCCESS;
                } else {
                    $this->status = $this::ERROR;
                    $this->message = "Unable to register or update the company, please try again later";
                }
            } else {
                $this->status = $this::ERROR;
                $this->message = "Unable to find user account, please log out and in and try again";
            }
            if ($this->data->getId()) {
                if ($this->_handleLogo($request, $path, $em) === false) {
                    $this->message = "Company information have been updated but the selected file was not an image type.";
                    $this->status = $this::ERROR;
                }
            }
        }

    }
}