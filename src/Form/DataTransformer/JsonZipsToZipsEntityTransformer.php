<?php

namespace App\Form\DataTransformer;

use App\Entity\Company;
use App\Entity\DeliveryZip;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class JsonZipsToZipsEntityTransformer implements DataTransformerInterface
{
    function __construct(private EntityManagerInterface $em, private Company $company) {}

    public function transform(mixed $zipEntities): string
    {
        if ($zipEntities instanceof ReadableCollection) {
            $zipEntityArray = $zipEntities->toArray();
            $zipStringArray = [];
            if (!is_array($zipEntityArray)) {
                return '[]';
            }
            foreach ($zipEntityArray as $zipEntity) {
                if ($zipEntity instanceof DeliveryZip) {
                    $zipCode = $zipEntity->getZipCode();
                    $zipStringArray[$zipCode] = $zipCode;
                }
            }
            return json_encode($zipStringArray);
        }
        return '[]';
    }

    public function reverseTransform(mixed $zipJsonString): ArrayCollection
    {
        if (is_string($zipJsonString)) {
            $zipArray = json_decode($zipJsonString, true);
            $zipEntityArray = [];

            if (is_array($zipArray) && $zipArray !== []) {
                foreach ($zipArray as $zipCode) {
                    if ($zipCode && $zipCode !== '') {
                        $zipEntity =
                            $this->em->getRepository(DeliveryZip::class)->findOneBy(
                                [
                                    'zipCode' => $zipCode,
                                    'company' => $this->company
                                ]
                            )
                            ?? new DeliveryZip();
                        $zipEntity->setZipCode($zipCode);
                        $zipEntity->setCompany($this->company);
                        $zipEntityArray[] = $zipEntity;
                    }
                }
                return new ArrayCollection($zipEntityArray);
            }
            return new ArrayCollection([]);
        }
        return new ArrayCollection([]);
    }
}