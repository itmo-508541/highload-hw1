<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[JMS\ExclusionPolicy('all')]
abstract class UserProvider implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const string ROLE_USER = 'ROLE_USER';

    public const string ROLE_API = 'ROLE_API';

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[JMS\Expose]
    protected string $username = '';

    #[ORM\Column(type: Types::JSON)]
    protected array $roles = [];

    #[ORM\Column(type: Types::STRING)]
    protected string $password = '';

    protected ?string $plainPassword = '';

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!\in_array(self::ROLE_USER, $roles, true)) {
            $roles[] = self::ROLE_USER;
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getRolesAsString(): string
    {
        $roles = $this->getRoles();
        if (\count($roles) > 1 && \in_array('ROLE_USER', $roles, true)) {
            $index = array_search('ROLE_USER', $roles, true);
            unset($roles[$index]);
        }

        return implode(', ', $roles);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $password): void
    {
        $this->plainPassword = $password;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    #[\Deprecated(message: 'Symfony 7.3, erase credentials using the "__serialize()" method instead')]
    public function eraseCredentials(): void
    {
    }
}
