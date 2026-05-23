<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceErrorRepository;
use App\Trait\DateTimeImmutableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'source_errors')]
#[ORM\Entity(repositoryClass: SourceErrorRepository::class)]
class SourceError
{
    use DateTimeImmutableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Source $source = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $exceptionClass = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $file = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $line = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Source|null
     */
    public function getSource(): ?Source
    {
        return $this->source;
    }

    /**
     * @param Source $source
     * @return $this
     */
    public function setSource(Source $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExceptionClass(): ?string
    {
        return $this->exceptionClass;
    }

    /**
     * @param string $exceptionClass
     * @return $this
     */
    public function setExceptionClass(string $exceptionClass): static
    {
        $this->exceptionClass = $exceptionClass;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * @param string|null $file
     * @return $this
     */
    public function setFile(?string $file): static
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLine(): ?int
    {
        return $this->line;
    }

    /**
     * @param int|null $line
     * @return $this
     */
    public function setLine(?int $line): static
    {
        $this->line = $line;

        return $this;
    }
}
