<?php

namespace App\Entity;

use App\Repository\OrderPartialRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderPartialRepository::class)]
class OrderPartial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderPartials')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $foodOrder = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dish $dish = null;

    #[ORM\Column]
    private ?string $size = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $priceSnapshot = null;

    #[ORM\Column(nullable: true)]
    private ?array $extras = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoodOrder(): ?Order
    {
        return $this->foodOrder;
    }

    public function setFoodOrder(?Order $food_order): static
    {
        $this->foodOrder = $food_order;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDish(): ?Dish
    {
        return $this->dish;
    }

    public function setDish(Dish $dish): static
    {
        $this->dish = $dish;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getPriceSnapshot(): ?float
    {
        return $this->priceSnapshot;
    }

    public function setPriceSnapshot(float $priceSnapshot): static
    {
        $this->priceSnapshot = $priceSnapshot;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function setExtras(?array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }
}
