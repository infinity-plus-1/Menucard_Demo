<?php

namespace App\Entity;

use App\Repository\ExtraRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExtraRepository::class)]
class Extra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dish $dish = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $selectType = null;

    #[ORM\ManyToOne(inversedBy: 'extra')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ExtrasGroup $extrasGroup = null;

    #[ORM\Column(length: 30)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'extras')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDish(): ?Dish
    {
        return $this->dish;
    }

    public function setDish(?Dish $dish): static
    {
        $this->dish = $dish;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getSelectType(): ?int
    {
        return $this->selectType;
    }

    public function setSelectType(int $selectType): static
    {
        $this->selectType = $selectType;

        return $this;
    }

    public function getExtrasGroup(): ?ExtrasGroup
    {
        return $this->extrasGroup;
    }

    public function setExtrasGroup(?ExtrasGroup $extrasGroup): static
    {
        $this->extrasGroup = $extrasGroup;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }
}
