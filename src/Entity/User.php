<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Get user roles as a string.
     *
     * @return string
     */
    public function getRolesAsString()
    {
        $roles = $this->getRoles();
        if (count($roles) > 1 && in_array(self::ROLE_DEFAULT, $roles)) {
            $index = array_search(self::ROLE_DEFAULT, $roles);
            unset($roles[$index]);
        }

        return implode(', ', $roles);
    }
}
