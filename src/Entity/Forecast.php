<?php

namespace App\Entity;

use App\Repository\ForecastRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForecastRepository::class)]
class Forecast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $direction = null;

    #[ORM\Column(precision: 4, scale: 1, nullable: true)]
    private ?float $entryPrice = null;

    #[ORM\Column(precision: 4, scale: 1, nullable: true)]
    private ?float $stopLoss = null;

    #[ORM\Column(precision: 4, scale: 1, nullable: true)]
    private ?float $takeProfit = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function getEntryPrice(): ?float
    {
        return $this->entryPrice;
    }

    public function setEntryPrice(?float $entryPrice): self
    {
        $this->entryPrice = $entryPrice;

        return $this;
    }

    public function getStopLoss(): ?float
    {
        return $this->stopLoss;
    }

    public function setStopLoss(?float $stopLoss): self
    {
        $this->stopLoss = $stopLoss;

        return $this;
    }

    public function getTakeProfit(): ?float
    {
        return $this->takeProfit;
    }

    public function setTakeProfit(?float $takeProfit): self
    {
        $this->takeProfit = $takeProfit;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
