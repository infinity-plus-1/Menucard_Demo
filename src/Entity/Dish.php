<?php

namespace App\Entity;

use App\Enum\DishCategoryEnum;
use App\Enum\DishTypeEnum;
use App\Repository\DishRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DishRepository::class)]
class Dish
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $img = null;

    #[ORM\ManyToOne(inversedBy: 'dishes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(nullable: true)]
    private ?array $sizes = null;

    #[ORM\Column(enumType: DishCategoryEnum::class)]
    private ?DishCategoryEnum $category = null;

    #[ORM\Column(enumType: DishTypeEnum::class)]
    private ?DishTypeEnum $type = null;

    /**
     * @var Collection<int, ExtrasGroup>
     */
    #[ORM\OneToMany(targetEntity: ExtrasGroup::class, mappedBy: 'dish', orphanRemoval: true)]
    private Collection $extrasGroups;

    /**
     * @var Collection<int, Extras>
     */
    #[ORM\OneToMany(targetEntity: Extra::class, mappedBy: 'dish', orphanRemoval: true)]
    private Collection $extras;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $deleted = null;

    public function __construct()
    {
        $this->extrasGroups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): static
    {
        $this->img = $img;

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

    public function getSizes(): ?array
    {
        return $this->sizes;
    }

    public function setSizes(?array $sizes): static
    {
        $this->sizes = $sizes;

        return $this;
    }

    public function getCategory(): ?DishCategoryEnum
    {
        return $this->category;
    }

    public function setCategory(DishCategoryEnum $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getType(): ?DishTypeEnum
    {
        return $this->type;
    }

    public function setType(DishTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function __get($prop)
    {
        return $this->$prop;
    }

    public function __isset($prop) : bool
    {
        return isset($this->$prop);
    }

    /**
     * @return Collection<int, ExtrasGroup>
     */
    public function getExtrasGroups(): Collection
    {
        return $this->extrasGroups;
    }

    public function addExtrasGroup(ExtrasGroup $extrasGroup): static
    {
        if (!$this->extrasGroups->contains($extrasGroup)) {
            $this->extrasGroups->add($extrasGroup);
            $extrasGroup->setDish($this);
        }

        return $this;
    }

    public function removeExtrasGroup(ExtrasGroup $extrasGroup): static
    {
        if ($this->extrasGroups->removeElement($extrasGroup)) {
            // set the owning side to null (unless already changed)
            if ($extrasGroup->getDish() === $this) {
                $extrasGroup->setDish(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Extras>
     */
    public function getExtras(): Collection
    {
        return $this->extras;
    }

    public function addExtra(Extra $extra): static
    {
        if (!$this->extras->contains($extra)) {
            $this->extras->add($extra);
            $extra->setDish($this);
        }

        return $this;
    }

    public function removeExtra(Extra $extra): static
    {
        if ($this->extras->removeElement($extra)) {
            // set the owning side to null (unless already changed)
            if ($extra->getDish() === $this) {
                $extra->setDish(null);
            }
        }

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }
}
