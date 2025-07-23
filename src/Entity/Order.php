<?php

namespace App\Entity;

use App\Enum\OrderStatusEnum;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, OrderPartial>
     */
    #[ORM\OneToMany(targetEntity: OrderPartial::class, mappedBy: 'foodOrder', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $orderPartials;

    #[ORM\Column]
    private ?\DateTime $created = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $done = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerNote = null;

    #[ORM\Column(enumType: OrderStatusEnum::class)]
    private ?OrderStatusEnum $status = null;

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rating_text = null;

    #[ORM\Column(length: 10)]
    private ?string $deliveryZip = null;

    #[ORM\Column(length: 60)]
    private ?string $deliveryCity = null;

    #[ORM\Column(length: 60)]
    private ?string $deliveryStreet = null;

    #[ORM\Column(length: 4)]
    private ?string $deliverySn = null;

    public function __construct()
    {
        $this->orderPartials = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, OrderPartial>
     */
    public function getOrderPartials(): Collection
    {
        return $this->orderPartials;
    }

    public function addOrderPartial(OrderPartial $orderPartial): static
    {
        if (!$this->orderPartials->contains($orderPartial)) {
            $this->orderPartials->add($orderPartial);
            $orderPartial->setFoodOrder($this);
        }

        return $this;
    }

    public function removeOrderPartial(OrderPartial $orderPartial): static
    {
        if ($this->orderPartials->removeElement($orderPartial)) {
            // set the owning side to null (unless already changed)
            if ($orderPartial->getFoodOrder() === $this) {
                $orderPartial->setFoodOrder(null);
            }
        }

        return $this;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getDone(): ?\DateTime
    {
        return $this->done;
    }

    public function setDone(?\DateTime $done): static
    {
        $this->done = $done;

        return $this;
    }

    public function getCustomerNote(): ?string
    {
        return $this->customerNote;
    }

    public function setCustomerNote(?string $customerNote): static
    {
        $this->customerNote = $customerNote;

        return $this;
    }

    public function getStatus(): ?OrderStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getRatingText(): ?string
    {
        return $this->rating_text;
    }

    public function setRatingText(?string $rating_text): static
    {
        $this->rating_text = $rating_text;

        return $this;
    }

    public function getDeliveryZip(): ?string
    {
        return $this->deliveryZip;
    }

    public function setDeliveryZip(string $deliveryZip): static
    {
        $this->deliveryZip = $deliveryZip;

        return $this;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(string $deliveryCity): static
    {
        $this->deliveryCity = $deliveryCity;

        return $this;
    }

    public function getDeliveryStreet(): ?string
    {
        return $this->deliveryStreet;
    }

    public function setDeliveryStreet(string $deliveryStreet): static
    {
        $this->deliveryStreet = $deliveryStreet;

        return $this;
    }

    public function getDeliverySn(): ?string
    {
        return $this->deliverySn;
    }

    public function setDeliverySn(string $deliverySn): static
    {
        $this->deliverySn = $deliverySn;

        return $this;
    }
}
