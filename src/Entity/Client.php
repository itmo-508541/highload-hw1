<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
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

    public function getId(): ?int
    {
        return $this->id;
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
