<?php

namespace App\Trait;

use App\Entity\Company;
use App\Entity\Extra;
use App\Entity\ExtrasGroup;
use App\Form\DataTransformer\JsonExtraToExtraEntityTransformer;
use App\Form\DataTransformer\JsonGroupToGroupEntityTransformer;
use App\Utility\Utility;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

trait ExtrasTrait
{
    protected function _validateExtras(array $decodedExtras, array|ArrayCollection $transformedExtras): bool
    {
        $count = 0;

        return true;
    }

    protected function _handleExtras($form, Company $company, EntityManagerInterface $em): array|bool
    {
        $multiExtras = NULL;
        $groups = NULL;
        try {
            $extrasJSON = $form->get('extras')->getData();
            if (!$extrasJSON) {
                return ['groups' => [], 'extras' => []];
            }
            $decodedExtras = json_decode($extrasJSON, true);

            if (!isset($decodedExtras['multiExtras']) || !isset($decodedExtras['singleExtras'])) {
                $this->status = 400;
                $this->message = "The data for the extras is invalid.";
                return false;
            }
            
            if (!is_array($decodedExtras['multiExtras']) || !is_array($decodedExtras['singleExtras'])) {
                $this->status = 400;
                $this->message = "The data for the extras is invalid.";
                return false;
            }

            $extrasTransformer = new JsonExtraToExtraEntityTransformer($em, $company, $this->product, 1);
            $multiExtras = $extrasTransformer->reverseTransform($decodedExtras['multiExtras']);
            
            foreach ($decodedExtras['singleExtras'] as $groupName => $group) {
                if (!is_array($group)) {
                    $this->status = 400;
                    $this->message = "The data for the extras is invalid.";
                    return false;
                }
                if (!isset($group['group']) || !isset($group['extras'])) {
                    $this->status = 400;
                    $this->message = "The data for the extras is invalid.";
                    return false;
                }
                if (!is_array($group['group']) || !is_array($group['extras'])) {
                    $this->status = 400;
                    $this->message = "The data for the extras is invalid.";
                    return false;
                }
                if ($group['extras'] === []) {
                    continue;
                }
                $name = $group['group']['name'] ?? NULL;
                if ($groupName !== $name || $name == '' || strlen($name) > 30) {
                    $this->status = 400;
                    $this->message = "The name for the group $name is invalid.";
                    return false;
                }

                $groupsTransformer = new JsonGroupToGroupEntityTransformer($em, $company, $this->product);

                $groups = $groupsTransformer->reverseTransform($extrasJSON);
            }
        } catch (\Throwable $th) {
            $this->status = 400;
            $this->message = "The data for the extras is invalid.";
            return false;
        }
        return ['groups' => $groups, 'extras' => $multiExtras];
    }

    protected function _saveExtras(array|ArrayCollection $extras, EntityManagerInterface $em, LoggerInterface $logger): bool
    {
        foreach ($extras as $extra) {
            if ($extra instanceof Extra) {
                $extra->setDish($this->product);
                $em->persist($extra);
            }
        }
        try {
            $em->flush();
        } catch (\Throwable $th) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }
            $this->status = Utility::ERROR;
            $this->message = 'Unexpected error occured while trying to save to the database.';
            $logger->error('Error: '.$th->getMessage(), ['exception' => $th]);
            return false;
        }
        return true;
    }

    protected function _saveGroups(array|ArrayCollection $groups, EntityManagerInterface $em, LoggerInterface $logger): bool
    {
        foreach ($groups as $group) {
            if ($group instanceof ExtrasGroup) {
                $group->setDish($this->product);
                $extras = $group->getExtras()->toArray();
                array_walk($extras, function ($extra) use ($em) {
                    if ($extra instanceof Extra) {
                        $em->persist($extra);
                    }
                });
                $em->persist($group);
            }
        }
        try {
            $em->flush();
        } catch (\Throwable $th) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }
            $this->status = Utility::ERROR;
            $this->message = 'Unexpected error occured while trying to save to the database.';
            dump($th);
            $logger->error('Error: '.$th->getMessage(), ['exception' => $th]);
            return false;
        }
        return true;
    }
}