<?php

namespace App\Mapper;

use App\Dto\DishPersistDto;
use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\Extra;
use App\Entity\ExtrasGroup;
use App\Utility\Utility;

class DishMapper
{
    public function dishEntityToDishPersistDto(Dish $dish, array $dishOptions): ?DishPersistDto
    {
        $company = $dish->getCompany() ?? NULL;
        if (Utility::isValidCompany($company)) {
            $company = $company->getId();
        } else {
            return NULL;
        }

        if (!$dish->getId()) {
            return NULL;
        }

        $totalPrice = $dish->getPrice();

        /** Process multi-selectable extras */

        $extras = $dish->getExtras();
        if (!$extras->isInitialized()) {
            $extras->initialize();
        }

        $extras = $extras->toArray();

        $cleanedExtras = [];
        $associatedExtras = [];

        foreach ($extras as $extra) {
            $cleanedExtras[$extra->getId()] = $extra->getName();
            $associatedExtras[$extra->getId()] = $extra;
        }

        $filteredExtras = [];

        foreach ($dishOptions['extras'] as $id => $unused) {
            if (isset($cleanedExtras[$id])) {
                $filteredExtras[$id] = $cleanedExtras[$id];
                $totalPrice += $associatedExtras[$id]->getPrice();
            } else {
                return NULL;
            }
        }

        /** Process single-selectable extras */

        $groups = $dish->getExtrasGroups();
        if (!$groups->isInitialized()) {
            $groups->initialize();
        }
        $cleanedGroups = array_filter (
            array_map(
                fn($group) => $group instanceof ExtrasGroup ? $group->getId() : NULL,
                $groups->toArray()
            ),
            fn($value) => $value !== NULL
        );

        $groups = $groups->toArray();

        $filteredGroups = [];

        foreach ($cleanedGroups as $id) {
            if (!isset($dishOptions['groups'][$id])) {
                return NULL;
            }
            if (!isset($dishOptions['groups'][$id]['extra']['extra'])) {
                return NULL;
            }
            if (!isset($dishOptions['groups'][$id]['groupName'])) {
                return NULL;
            }
            $extraId = $dishOptions['groups'][$id]['extra']['extra'];
            if (isset($associatedExtras[$extraId])) {
                $extra = $associatedExtras[$extraId];
                if ($extra->getSelectType() === 2 && $extra->getExtrasGroup() instanceof ExtrasGroup) {
                    $group = $extra->getExtrasGroup();
                    if ($group->getId() === $id) {
                        $extraName = $extra->getName();
                        $groupName = $group->getName();
                        $filteredGroups[$groupName] = [$extraId => $extraName];
                        $totalPrice += $extra->getPrice();
                    } else {
                        return NULL;
                    }
                } else {
                    return NULL;
                }
            } else {
                return NULL;
            }
        }

        /** Process size */

        $dishSizes = array_filter($dish->getSizes(), fn($size) => floatval($size) > 0.0);

        if (
            $dishSizes !== []
            && (
                !isset($dishOptions['size'])
                || !isset($dishOptions['size']['size'])
                || !isset($dishOptions['size']['price'])
                || !isset($dishSizes[$dishOptions['size']['size']])
            )
        ) {
            return NULL;
        }

        $totalPrice += $dishOptions['size']['price'];

        $dishPersistDto = new DishPersistDto(
            id: $dish->getId(),
            name: $dish->getName(),
            description: $dish->getDescription(),
            price: $dish->getPrice(),
            totalPrice: $totalPrice,
            img: $dish->getImg(),
            company: $company,
            size: $dishOptions['size']['size'],
            category: $dish->getCategory()->value,
            type: $dish->getType()->value,
            extras: $filteredExtras,
            extrasGroups: $filteredGroups,
        );
        return $dishPersistDto;
    }
}