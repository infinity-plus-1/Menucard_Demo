<?php

namespace App\Utility;

use App\Entity\Company;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;

final class Paths
{
    const PUBLIC = 1;
    const PRIVATE = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Kernel $kernel
    ) {}

    public function getPublicUploadsDir(bool $relative = false): string
    {
        return (!$relative ? $this->kernel->getProjectDir() . '/public/' : '') . 'uploads';
    }

    public function getPrivateUploadsDir(): string
    {
        return $this->kernel->getProjectDir() . '/var/uploads';
    }

    public function getLogoDir(bool $relative = false): string
    {
        return Paths::getPublicUploadsDir($relative) . '/logos';
    }

    public function getCompanyHashDir(string|int $company): string
    {
        $company = is_int($company)
            ? $this->entityManager->getRepository(Company::class)->find($company)
            : $this->entityManager->getRepository(Company::class)->findOneBy(['name' => $company])
        ;
        if (Utility::isValidCompany($company)) {
            return hash('sha256', $company->getName());
        } elseif (is_string($company) && $company !== '') {
            return hash('sha256', $company);
        } else {
            return '';
        }
    }

    public function getCompanyLogoDir(string|int $company): string
    {
        $hashDir = Paths::getCompanyHashDir($company);
        if ($hashDir === '') return $hashDir;

        return Paths::getLogoDir() . '/' . $hashDir;
    }

    public function getCompanyRelativeLogoDir(string|int $company): string
    {
        $hashDir = Paths::getCompanyHashDir($company);
        if ($hashDir === '') return $hashDir;

        return Paths::getLogoDir(true) . '/' . $hashDir;
    }

    public function getDishDir(string $company, int $dishId): string
    {
        return $this->getPublicUploadsDir(true) . '/' . $this->getCompanyHashDir($company) . '/' . hash('sha256', strval($dishId));
    }

    public function getRelativeDishDir(string $company, int $dishId): string
    {
        return $this->getPublicUploadsDir() . '/' . $this->getCompanyHashDir($company) . '/' . hash('sha256', strval($dishId));
    }
}