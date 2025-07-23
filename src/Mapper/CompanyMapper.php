<?php

namespace App\Mapper;

use App\Dto\CompanyDto;
use App\Entity\Company;
use App\Entity\DeliveryZip;
use App\Entity\Dish;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class CompanyMapper
{
    public function entityToDto(Company $company): CompanyDto
    {
        $dishes = $company->getDishes();
        $dishes = $dishes->map(fn($dish) => $dish->getId())->toArray();
        $deliveryZips = $company->getDeliveryZips();
        $deliveryZips = $deliveryZips->map(fn($zip) => $zip->getId())->toArray();

        return new CompanyDto (
            $company->getId(),
            $company->getName(),
            $company->getType(),
            $company->getZip(),
            $company->getCity(),
            $company->getStreet(),
            $company->getSn(),
            $company->getPhone(),
            $company->getEmail(),
            $company->getWebsite(),
            $company->getTax(),
            $company->getLogo(),
            $dishes,
            $deliveryZips
        );
    }

    public function dtoToEntity(CompanyDto $companyDto, EntityManagerInterface $em): Company
    {
        $company = new Company();
        $company->setName($companyDto->name);
        $company->setType($companyDto->type);
        $company->setZip($companyDto->zip);
        $company->setCity($companyDto->city);
        $company->setStreet($companyDto->street);
        $company->setSn($companyDto->sn);
        $company->setEmail($companyDto->email);
        $company->setPhone($companyDto->phone);
        $company->setWebsite($companyDto->website);
        $company->setTax($companyDto->tax);
        $company->setLogo($companyDto->logo);
        if ($companyDto->dishes && $companyDto->dishes !== []) {
            $dishes = $em->getRepository(Dish::class)->findBy(['id' => $companyDto->dishes]);
            foreach ($dishes as $dish) {
                $company->addDish($dish);
            }
        }
        if ($companyDto->deliveryZips && $companyDto->deliveryZips !== []) {
            $deliveryZips = $em->getRepository(DeliveryZip::class)->findBy(['id' => $companyDto->deliveryZips]);
            foreach ($deliveryZips as $zip) {
                $company->addDeliveryZip($zip);
            }
        }
        return $company;
    }
}