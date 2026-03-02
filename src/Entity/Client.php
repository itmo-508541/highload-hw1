<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ClientRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="confirmation_token", columns={"confirmation_token"})
 *     }
 * )
 * @JMS\ExclusionPolicy("all")
 */
#[UniqueEntity(fields: ['username'], errorPath: 'username', ignoreNull: false)]
class Client implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE = 'ROLE_API';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @phpstan-ignore property.unusedType
     */
    private ?int $id = null;

    /**
     * @JMS\Expose()
     * @ORM\Column(type="string", length=180, unique=true)
     */
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $username;

    /**
     * @ORM\Column(type="string")
     */
    private string $password;

    /**
     * @JMS\Expose()
     * @ORM\Column(type="string", length=64)
     */
    #[Assert\NotBlank]
    private ?string $firstname = null;

    /**
     * @JMS\Expose()
     * @ORM\Column(type="string", length=64)
     */
    #[Assert\NotBlank]
    private ?string $lastname = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $confirmedAt = null;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private ?string $confirmationToken = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }

    public function getId(): int
    {
        return $this->id;
    }

    /** @internal used only inside sonata admin */
    public function getToken(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getConfirmedAt(): ?\DateTime
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTime $confirmedAt): void
    {
        $this->confirmedAt = $confirmedAt;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function getRoles(): array
    {
        return [self::ROLE];
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }
}
