<?php

namespace App\Form\DataTransformer;

use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\Extra;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class JsonExtraToExtraEntityTransformer implements DataTransformerInterface
{
    function __construct(private EntityManagerInterface $em, private Company $company, private ?Dish $dish, private int $type) {}

    public function transform(mixed $extras): string
    {
        if (is_array($extras) || $extras instanceof ArrayCollection) {
            $transformedExtras = [];
            foreach ($extras as $extra) {
                if (!$extra instanceof Extra) {
                    continue;
                }
                $type = $extra->getSelectType();
                if ($type !== $this->type) {
                    continue;
                }
                $name = $extra->getName();
                $price = $extra->getPrice();
                $id = $extra->getId();
                $transformedExtras[$id] = [];
                $transformedExtras[$id]['name'] = $name;
                $transformedExtras[$id]['price'] = $price;
                $transformedExtras[$id]['identifier'] = $id;
                $transformedExtras[$id]['type'] = $type;
            }
            $encodedExtras =  json_encode($transformedExtras);
            return $encodedExtras === '[]' ? '{}' : $encodedExtras;

        }
        return '{}';
    }

    public function reverseTransform(mixed $extras): ArrayCollection
    {
        $extrasArray = [];
        if (is_string($extras)) {
            $extrasArray = json_decode($extras, true);
        } else if (is_array($extras)) {
            $extrasArray = $extras;
        } else {
            return new ArrayCollection([]);
        }

        //In case we got all extras, we just need to extract the multi-selectable.
        if ($this->type === 1 && isset($extrasArray['multiExtras'])) {
            $extrasArray = $extrasArray['multiExtras'];
        } 

        $extrasEntityArray = [];

        if (is_array($extrasArray) && $extrasArray !== []) {
            foreach ($extrasArray as $extra) {
                if (is_array($extra) && $extra !== []) {
                    $extraEntity =
                        $this->em->getRepository(Extra::class)->findOneBy(
                            [
                                'id' => $extra['identifier'],
                                'name' => $extra['name'],
                                'company' => $this->company,
                                'dish' => $this->dish,
                                'selectType' => $this->type,
                            ]
                        )
                        ?? new Extra();

                    $extraEntity->setName($extra['name']);
                    $extraEntity->setPrice($extra['price']);
                    $extraEntity->setCompany($this->company);
                    $extraEntity->setDish($this->dish);
                    $extraEntity->setSelectType($this->type);
                    
                    $extrasEntityArray[] = $extraEntity;
                }
            }
            return new ArrayCollection($extrasEntityArray);
        }
        return new ArrayCollection([]);
    }
}