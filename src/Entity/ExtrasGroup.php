<?php

namespace App\Entity;

use App\Repository\ExtrasGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExtrasGroupRepository::class)]
class ExtrasGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'extrasGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'extrasGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dish $dish = null;

    /**
     * @var Collection<int, Extras>
     */
    #[ORM\OneToMany(targetEntity: Extra::class, mappedBy: 'extrasGroup', orphanRemoval: true)]
    private Collection $extras;

    public function __construct()
    {
        $this->extras = new ArrayCollection();
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
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
            $extra->setExtrasGroup($this);
        }

        return $this;
    }

    public function removeExtra(Extra $extra): static
    {
        if ($this->extras->removeElement($extra)) {
            // set the owning side to null (unless already changed)
            if ($extra->getExtrasGroup() === $this) {
                $extra->setExtrasGroup(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return "
            id: $this->id,
            name: $this->name,
        ";
    }
}
