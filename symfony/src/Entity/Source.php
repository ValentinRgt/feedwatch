<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FormatEnum;
use App\Enum\PeriodicityEnum;
use App\Enum\StatusEnum;
use App\Repository\SourceRepository;
use App\Trait\DateTimeImmutableTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'sources')]
#[ORM\Entity(repositoryClass: SourceRepository::class)]
class Source
{
    use DateTimeImmutableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $url = null;

    #[ORM\Column(type: Types::STRING, length: 100, enumType: FormatEnum::class)]
    private ?FormatEnum $format = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $checksum = null;

    #[ORM\ManyToOne(inversedBy: 'sources')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column(type: Types::STRING, length: 100, enumType: StatusEnum::class)]
    private ?StatusEnum $status = null;

    #[ORM\Column(type: Types::STRING, length: 100, enumType: PeriodicityEnum::class)]
    private ?PeriodicityEnum $periodicity = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastFetchedAt = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    /**
     * @param string|null $checksum
     * @return $this
     */
    public function setChecksum(?string $checksum): static
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * @return FormatEnum|null
     */
    public function getFormat(): ?FormatEnum
    {
        return $this->format;
    }

    /**
     * @param FormatEnum $format
     * @return void
     */
    public function setFormat(FormatEnum $format): void
    {
        $this->format = $format;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     * @return $this
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return StatusEnum|null
     */
    public function getStatus(): ?StatusEnum
    {
        return $this->status;
    }

    /**
     * @param StatusEnum $status
     * @return void
     */
    public function setStatus(StatusEnum $status): void
    {
        $this->status = $status;
    }

    /**
     * @return PeriodicityEnum|null
     */
    public function getPeriodicity(): ?PeriodicityEnum
    {
        return $this->periodicity;
    }

    /**
     * @param PeriodicityEnum $periodicity
     * @return void
     */
    public function setPeriodicity(PeriodicityEnum $periodicity): void
    {
        $this->periodicity = $periodicity;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getLastFetchedAt(): ?DateTimeImmutable
    {
        return $this->lastFetchedAt;
    }

    /**
     * @param DateTimeImmutable|null $lastFetchedAt
     * @return $this
     */
    public function setLastFetchedAt(?DateTimeImmutable $lastFetchedAt): static
    {
        $this->lastFetchedAt = $lastFetchedAt;

        return $this;
    }
}
