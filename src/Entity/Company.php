<?php

namespace App\Entity;

use App\Enum\CuisinesEnum;
use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(enumType: CuisinesEnum::class)]
    private ?CuisinesEnum $type = null;

    #[ORM\Column(length: 255)]
    private ?string $zip = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\Column(length: 255)]
    private ?string $sn = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255)]
    private ?string $tax = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    /**
     * @var Collection<int, Dish>
     */
    #[ORM\OneToMany(targetEntity: Dish::class, mappedBy: 'company')]
    private Collection $dishes;

    /**
     * @var Collection<int, DeliveryZip>
     */
    #[ORM\OneToMany(targetEntity: DeliveryZip::class, mappedBy: 'company', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $deliveryZips;

    #[ORM\Column(nullable: true)]
    private ?float $averageRating = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalRatings = null;

    /**
     * @var Collection<int, ExtrasGroup>
     */
    #[ORM\OneToMany(targetEntity: ExtrasGroup::class, mappedBy: 'company', orphanRemoval: true)]
    private Collection $extrasGroups;

    /**
     * @var Collection<int, Extra>
     */
    #[ORM\OneToMany(targetEntity: Extra::class, mappedBy: 'company', orphanRemoval: true)]
    private Collection $extras;

    #[ORM\Column]
    private ?bool $deleted = null;

    public function __construct()
    {
        $this->dishes = new ArrayCollection();
        $this->deliveryZips = new ArrayCollection();
        $this->extrasGroups = new ArrayCollection();
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

    public function getType(): ?CuisinesEnum
    {
        return $this->type;
    }

    public function setType(CuisinesEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getSn(): ?string
    {
        return $this->sn;
    }

    public function setSn(string $sn): static
    {
        $this->sn = $sn;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getTax(): ?string
    {
        return $this->tax;
    }

    public function setTax(string $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return Collection<int, Dish>
     */
    public function getDishes(): Collection
    {
        return $this->dishes;
    }

    public function addDish(Dish $dish): static
    {
        if (!$this->dishes->contains($dish)) {
            $this->dishes->add($dish);
            $dish->setCompany($this);
        }

        return $this;
    }

    public function removeDish(Dish $dish): static
    {
        if ($this->dishes->removeElement($dish)) {
            // set the owning side to null (unless already changed)
            if ($dish->getCompany() === $this) {
                $dish->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DeliveryZip>
     */
    public function getDeliveryZips(): Collection
    {
        return $this->deliveryZips;
    }

    public function addDeliveryZip(DeliveryZip $deliveryZip): static
    {
        if (!$this->deliveryZips->contains($deliveryZip)) {
            $this->deliveryZips->add($deliveryZip);
            $deliveryZip->setCompany($this);
        }

        return $this;
    }

    public function removeDeliveryZip(DeliveryZip $deliveryZip): static
    {
        if ($this->deliveryZips->removeElement($deliveryZip)) {
            // set the owning side to null (unless already changed)
            if ($deliveryZip->getCompany() === $this) {
                $deliveryZip->setCompany(null);
            }
        }

        return $this;
    }

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function setAverageRating(?float $averageRating): static
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    public function getTotalRatings(): ?int
    {
        return $this->totalRatings;
    }

    public function setTotalRatings(?int $totalRatings): static
    {
        $this->totalRatings = $totalRatings;

        return $this;
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
            $extrasGroup->setCompany($this);
        }

        return $this;
    }

    public function removeExtrasGroup(ExtrasGroup $extrasGroup): static
    {
        if ($this->extrasGroups->removeElement($extrasGroup)) {
            // set the owning side to null (unless already changed)
            if ($extrasGroup->getCompany() === $this) {
                $extrasGroup->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Extra>
     */
    public function getExtras(): Collection
    {
        return $this->extras;
    }

    public function addExtra(Extra $extra): static
    {
        if (!$this->extras->contains($extra)) {
            $this->extras->add($extra);
            $extra->setCompany($this);
        }

        return $this;
    }

    public function removeExtra(Extra $extra): static
    {
        if ($this->extras->removeElement($extra)) {
            // set the owning side to null (unless already changed)
            if ($extra->getCompany() === $this) {
                $extra->setCompany(null);
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
