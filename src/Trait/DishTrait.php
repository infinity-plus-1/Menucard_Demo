<?php

namespace App\Trait;

use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\Extra;
use App\Entity\ExtrasGroup;
use App\Entity\User;
use App\Form\DataTransformer\JsonExtraToExtraEntityTransformer;
use App\Form\DataTransformer\JsonGroupToGroupEntityTransformer;
use App\Utility\Paths;
use App\Utility\Utility;
use DirectoryIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

trait DishTrait
{
    use ExtrasTrait;

    #[LiveProp(writable:true)]
    public ?Dish $product = NULL;

    #[LiveProp]
    public ?string $message = NULL;

    #[LiveProp]
    public ?int $status = NULL;

    #[LiveProp]
    public string $preserveImg = '';

    private bool $isUpdate = false;

    public function isValidForm(): bool {
        return $this->getForm()->isSubmitted() && $this->getForm()->isValid();
    }

    protected function _handleImage(Request $request, User $user, EntityManagerInterface $em, int $id, Paths $path): bool {
        $filesystem = new Filesystem();
        $dishPath = $path->getRelativeDishDir($user->getCompany()->getName(), $this->product->getId());

        if ($dishPath !== '' && !$filesystem->exists($dishPath)) {
            $filesystem->mkdir($dishPath);
        }
        if ($filesystem->exists($dishPath)) {
            $file = NULL;

            $files = $request->files->all();

            if (is_array($files) && isset($files['dish'])) {
                if (is_array($files['dish']) && isset($files['dish']['img'])) {
                    $file = $files['dish']['img'];
                }
            }

            if ($file instanceof UploadedFile && $file->isFile() && $dishPath !== '') {
                $fileMime = $file->getClientMimeType();
                if ($fileMime && $fileMime !== '') {
                    $fileMime = explode('/', $fileMime);
                    if (is_array($fileMime) && count($fileMime) > 1) {
                        if (trim($fileMime[0]) === 'image') {
                            $iterator = new DirectoryIterator($dishPath);
                            while ($iterator->valid()) {
                                if (!$iterator->isDot()) {
                                    $filesystem->remove($iterator->getPathname());
                                }
                                $iterator->next();
                            }
                            $file->move($dishPath, 'dish_img.jpg');
                            $this->product->setImg($path->getDishDir($user->getCompany()->getName(), $this->product->getId())
                                . '/dish_img.jpg');
                            $em->flush();
                        } else {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    

    #[LiveAction]
    public function save(Request $request, EntityManagerInterface $em, Paths $path, LoggerInterface $logger): void
    {
        $form = $this->getForm();
        
        $this->status = Utility::OPEN;
        $this->message = '';
        try {
            if (!$form->isSubmitted()) {
                $this->submitForm();
            }
        } catch (\Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException $e) {
            $this->status = Utility::ERROR;
            $this->message = 'Unable to validate the form. ' .
                'Please verify your data and reload with cleared caches.';
            return;
        } catch (\Throwable $e) {
            $this->status = Utility::ERROR;
            $this->message = 'An unexpected error occured while trying to validate the form.' .
                'Please verify your data and reload with cleared caches.';
            $logger->error('Error: '.$e->getMessage(), ['exception' => $e]);
        }

        $user = $this->getUser();
        
        if ($form->isSubmitted() && $form->isValid()) {
           
            if ($user instanceof User) {
                $company = $user->getCompany();
                if (!Utility::isValidCompany($company)) {
                    $this->message = "Your company information have not been set yet. Please click in the navigation list of your" .
                        " dashboard on 'Company' and enter all needed information there first.";
                    $this->status = Utility::ERROR;
                }

                if ($this->status !== Utility::ERROR) {
                    $this->product->setCompany($company);
                    try {
                        $extras = $this->_handleExtras($form, $company, $em);
                        if (!$extras) {
                            return;
                        }

                        $this->product->setDeleted(false);
                        $em->persist($this->product);
                        $em->flush();

                        if ($this->product->getId() != NULL) {
                            if (isset($extras['groups'])) {
                                $res = $this->_saveGroups($extras['groups'], $em, $logger);
                                if (!$res) {
                                    return;
                                }
                            }
                            if (isset($extras['extras'])) {
                                $res = $this->_saveExtras($extras['extras'], $em, $logger);
                                if (!$res) {
                                    return;
                                }
                            }
                        }
                    } catch (\Doctrine\ORM\OptimisticLockException $e) {
                        if ($em->getConnection()->isTransactionActive()) {
                            $em->rollback();
                        }
                        $this->status = Utility::ERROR;
                        $this->message = 'Simultaneous writing of the same object to the database. Please try again.';
                        return;
                    } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                        if ($em->getConnection()->isTransactionActive()) {
                            $em->rollback();
                        }
                        $this->status = Utility::ERROR;
                        $this->message = 'You have tried to store an unique value to the database.';
                        return;
                    } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
                        if ($em->getConnection()->isTransactionActive()) {
                            $em->rollback();
                        }
                        $this->status = Utility::ERROR;
                        $this->message = 'A constraint violation of the database has been detected.';
                        return;
                    } catch (\Throwable $e) {
                        if ($em->getConnection()->isTransactionActive()) {
                            $em->rollback();
                        }
                        $this->status = Utility::ERROR;
                        $this->message = 'Unexpected error occured while trying to save to the database.';
                        $logger->error('Error: '.$e->getMessage(), ['exception' => $e]);
                        return;
                    }
                    
                    if (!$this->isUpdate && $this->product->getId()) {
                        $this->_handleImage($request, $user, $em, $this->product->getId(), $path);
                        $this->message = "Dish created. Create some more! The page will reload in 5 seconds automatically.";
                        $this->status = Utility::SUCCESS;
                    } elseif ($this->isUpdate) {
                        $this->_handleImage($request, $user, $em, $this->product->getId(), $path);
                        $this->message = "Dish updated. This window will close in 5 seconds automatically.";
                        $this->status = Utility::SUCCESS;
                    } else {
                        $this->status = Utility::ERROR;
                        $this->message = "Unable to create the dish. Please clear the cache and reload the page.";
                    }
                }
            } 
        }
        if ($this->isUpdate) {
            $this->emit('dish_updated');
        }
    }

    #[LiveAction]
    public function update(Request $request, EntityManagerInterface $em, Paths $path, LoggerInterface $logger): void
    {
        $form = $this->getForm();
        //instanceof Dish for intelephense
        if ($this->product instanceof Dish && $this->product->getId() && !$this->product->isDeleted()) {
            $imgPath = $this->product->getImg();
            //instanceof Form for intelephense
            if ($form instanceof Form && !$form->isSubmitted()) {
                $this->submitForm();
                if ($form->isValid()) {
                    switch ($this->product->getImg()) {
                        case '':
                            $this->product->setImg($imgPath);
                            break;
                        case 'delete':
                            $this->product->setImg('');
                    }
                    $this->isUpdate = true;
                    $this->save($request, $em, $path, $logger);
                }
            }
        }
    }

    #[LiveAction]
    public function delete(#[LiveArg('dishId')] int $dishId, EntityManagerInterface $em, Paths $path): void
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $this->status = Utility::ERROR;
                $this->message = "Response: 401 - Unauthorized";
                $this->emit('dish_updated');
                return;
            }

            $dish = $em->getRepository(Dish::class)->find($dishId);
            if (!$dish) {
                $this->status = Utility::ERROR;
                $this->message = "Response: 404 - Dish not found";
                $this->emit('dish_updated');
                return;
            }

            $company = $user->getCompany();
            if (!Utility::isValidCompany($company) || $dish->getCompany() !== $company) {
                $this->status = Utility::ERROR;
                $this->message = "Response: 403 - Forbidden";
                $this->emit('dish_updated');
                return;
            }

            $imgPath = $path->getRelativeDishDir($company->getName(), $dish->getId());
            $filesystem = new Filesystem();
            if ($filesystem->exists($imgPath)) {
                $filesystem->remove($imgPath);
            }

            $em->getConnection()->beginTransaction();
            
            $dish->setDeleted(true);
            $em->flush();
            $em->getConnection()->commit();

            $this->emit('dish_updated');

            $this->message = "Dish deleted";
            $this->status = Utility::SUCCESS;

            $this->dispatchBrowserEvent('dish_deleted', [
                'message' => $this->message,
                'status' => $this->status,
            ]);

        } catch (\Throwable $e) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->getConnection()->rollBack();
            }

            $this->logger->error('Delete failed: '.$e->getMessage());

            $this->status = Utility::ERROR;
            $this->message = "Response: 500 - Internal server error";

            $this->dispatchBrowserEvent('dish_deleted', [
                'message' => $this->message,
                'status' => $this->status,
            ]);
        }
    }

    protected function getDish(int $id, EntityManagerInterface $em): ?Dish
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $company = $user->getCompany();
            if (Utility::isValidCompany($company)) {
                $companyId = $company->getId();
                $dishObj = $em->getRepository(Dish::class)->find($id);
                if ($dishObj instanceof Dish) {
                    $company = $dishObj->getCompany();
                    if (Utility::isValidCompany($company)) {
                        $companyVarifyId = $company->getId();
                        if (is_int($companyId) && is_int($companyVarifyId) && $companyId === $companyVarifyId) {
                            return $dishObj;
                        }
                        return NULL;
                    }
                    return NULL;
                }
                return NULL;
            }
            return NULL;
        }
        return NULL;
    }
}
