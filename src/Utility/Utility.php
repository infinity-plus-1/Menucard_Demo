<?php

namespace App\Utility;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\PersistentCollection;

final class Utility
{
    const SUCCESS = 1;
    const ERROR = 2;
    const OPEN = 3;
    
    /** 
     * Check whether the user's zipcode is supported by the restaurant or not
     * @param User $user The user entity
     * @param Company $company The restaurant's entity
     * 
     * @return bool True if the user's zipcode is supported and false instead
     */
    public static function isInDeliveryRange(Company $company, ?User $user, ?string $zip = NULL): bool
    {
        $supportedZips = $company->getDeliveryZips() ?? $company->getZip();
        if ($supportedZips instanceof PersistentCollection) {
            $supportedZips = array_map(fn($zip) => $zip->getZipCode(), $supportedZips->getValues());
        }
        $userZip = $zip ?? ($user ? $user->getZipcode() : '00000');

        if ($user) {
            if (self::isCompanyAccount($user)) { // Commercial accounts are not allowed to order food.
                return false;
            }
        }

        return
            (is_array($supportedZips) && array_search($userZip, $supportedZips) !== false)
            ? true
            : ((!is_array($supportedZips) && $userZip === $supportedZips)
            ? true
            : false
            );
            
    }

    /** 
     * Check whether the account is registered as a commercial user
     * @param User $user The user entity
     * 
     * @return bool True if the account contains the ROLE_COMPANY role
     */
    public static function isCompanyAccount(User $user): bool
    {
        return in_array('ROLE_COMPANY', $user->getRoles());
    }

    /** 
     * Check whether the account is registered as a commercial user AND a company has been registered already
     * @param User $user The user entity
     * 
     * @return bool True if the account contains the ROLE_COMPANY role and a company exists
     */
    public static function accountIsSetUp(User $user): bool
    {
        $company = $user->getCompany();
        return !self::isCompanyAccount($user) || (self::isCompanyAccount($user) && $company && $company instanceof Company);
    }
}