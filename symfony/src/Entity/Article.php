<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArticleRepository;
use App\Trait\DateTimeImmutableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'articles')]
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    use DateTimeImmutableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $checksum = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $link = null;

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
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @param string|null $link
     * @return $this
     */
    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }
}
