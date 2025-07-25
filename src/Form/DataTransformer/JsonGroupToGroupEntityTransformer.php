<?php

namespace App\Form\DataTransformer;

use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\Extra;
use App\Entity\ExtrasGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class JsonGroupToGroupEntityTransformer implements DataTransformerInterface
{
    function __construct(private EntityManagerInterface $em, private Company $company, private Dish $dish) {}

    public function transform(mixed $groups): string
    {
        if (is_array($groups)) {
            $transformedGroups = [];
            foreach ($groups as $group) {
                if ($group instanceof ExtrasGroup) {

                    $name = $group->getName();
                    $extras = $group->getExtras();
                    $id = $group->getId();
                    $transformedGroups[$name] = [];
                    $transformedExtras = [];
                    if (!is_array($extras) && !$extras instanceof ReadableCollection) {
                        continue;
                    }
                    
                    $transformedGroups[$name]['group'] = [];
                    $transformedGroups[$name]['group']['name'] = $name;
                    $transformedGroups[$name]['group']['identifier'] = $id;
                    $transformedGroups[$name]['extras'] = [];

                    $transformedExtras = [];
                    foreach ($extras as $extra) {
                        if (!$extra instanceof Extra) {
                            continue;
                        }
                        $extraName = $extra->getName();
                        $price = $extra->getPrice();
                        $id = $extra->getId();
                        $transformedExtras[$extraName] = [];
                        $transformedExtras[$extraName]['name'] = $extraName;
                        $transformedExtras[$extraName]['price'] = $price;
                        $transformedExtras[$extraName]['identifier'] = $id;
                    }
                    $transformedGroups[$name]['extras'] = $transformedExtras;
                }
            }
            
            $encodedGroups = json_encode($transformedGroups);
            return $encodedGroups === '[]' ? '{}' : $encodedGroups;
        }
        return '{}';
    }

    public function reverseTransform(mixed $groupsInput): ArrayCollection
    {
        if (is_string($groupsInput)) {
            $groupsInput = json_decode($groupsInput, true);
        }
        
        if (!is_array($groupsInput) || !isset($groupsInput['singleExtras'])) {
            return new ArrayCollection([]);
        }
        
        $groupsArray = $groupsInput;
        $groupsEntityArray = [];

        $extrasTransformer = NULL;

        try {
            $extrasTransformer = new JsonExtraToExtraEntityTransformer($this->em, $this->company, $this->dish, 2);

        } catch (\Throwable $th) {
            return new ArrayCollection([]);
        }

        if (is_array($groupsArray) && $groupsArray !== []) {
            if (isset($groupsArray['singleExtras'])) {
                $groupsArray = $groupsArray['singleExtras'];
            }

            $groupEntities = $this->em->getRepository(ExtrasGroup::class)->findBy(
                [
                    'company' => $this->company,
                    'dish' => $this->dish,
                ]
            );

            $groupEntitiesById = [];

            foreach ($groupEntities as $entity) {
                $groupEntitiesById[$entity->getId()] = $entity;
            }
            
            foreach ($groupsArray as $group) {
                if (!isset($group['group']) || !isset($group['group']['name']) || !isset($group['extras'])) {
                    continue;
                }
                if (is_array($group) && $group !== []) {

                    if (!isset($groupEntitiesById[$group['group']['identifier']])) {
                        $groupEntity = new ExtrasGroup();
                    } else {
                        $groupEntity = $groupEntitiesById[$group['group']['identifier']];
                    }

                    $name = $group['group']['name'];
                    $extras = $group['extras'];
                    $groupEntity->setName($name);

                    $extraEntities = $extrasTransformer->reverseTransform($extras);

                    array_map(function($extra) use (&$groupEntity) { $groupEntity->addExtra($extra); }, $extraEntities->toArray());

                    $groupEntity->setCompany($this->company);
                    $groupEntity->setDish($this->dish);


                    $groupsEntityArray[$name] = $groupEntity;
                }
            }
            return new ArrayCollection($groupsEntityArray);
        }
        return new ArrayCollection([]);
    }
}