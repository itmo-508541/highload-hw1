<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    protected string $value;

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
