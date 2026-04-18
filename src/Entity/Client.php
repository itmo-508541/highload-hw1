<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'apiKey', columns: ['apiKey']),
    ],
)]
#[UniqueEntity(fields: ['apiKey'], message: 'This apiKey is already in use.')]
class Client extends UserProvider
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['api'])]
    protected ?int $id = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['api'])]
    protected array $roles = [self::ROLE_USER, self::ROLE_API];

    #[ORM\Column(type: Types::STRING, length: 48, nullable: true)]
    #[Assert\Length(max: 48)]
    #[Ignore]
    protected ?string $clientAccount = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Ignore]
    protected ?string $apiKey = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientAccount(): ?string
    {
        return $this->clientAccount;
    }

    public function setClientAccount(?string $clientAccount): void
    {
        $this->clientAccount = $clientAccount;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /** @internal used only inside sonata admin, should not be used outside, always null */
    #[Ignore]
    public function getPermanentAccessToken(): ?string
    {
        return null;
    }

    /** @internal used only inside sonata admin, should not be used outside, always null */
    #[Ignore]
    public function getTemporaryAccessToken(): ?string
    {
        return null;
    }
}
