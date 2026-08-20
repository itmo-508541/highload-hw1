<?php

declare(strict_types=1);

namespace App\Behat;

use App\Entity\User;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * E2E: форма /admin/login → App\Security\UserAuthentificatorAuthenticator + access_control.
 */
final class AdminLoginContext extends RawMinkContext
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[BeforeScenario]
    public function resetBrowserSession(): void
    {
        $session = $this->getSession();
        if ($session->isStarted()) {
            $session->restart();
        }
    }

    #[Given('существует пользователь :username с паролем :password и ролью :role')]
    public function thereIsAUserWithPasswordAndRole(string $username, string $password, string $role): void
    {
        $repository = $this->em->getRepository(User::class);
        $user = $repository->findOneBy(['username' => $username]) ?? new User();

        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles([$role]);

        $this->em->persist($user);
        $this->em->flush();
    }

    #[When('я открываю страницу входа в админку')]
    public function iOpenTheAdminLoginPage(): void
    {
        $this->visitPath('/admin/login');
    }

    #[When('я вхожу как :username с паролем :password')]
    public function iLogInAs(string $username, string $password): void
    {
        $page = $this->getSession()->getPage();
        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('Log in');
    }

    #[Then('текущий URL должен содержать :fragment')]
    public function currentUrlShouldContain(string $fragment): void
    {
        $url = $this->getSession()->getCurrentUrl();
        if (!str_contains($url, $fragment)) {
            throw new \RuntimeException(\sprintf('Expected URL to contain "%s", got "%s".', $fragment, $url));
        }
    }

    #[Then('я должен видеть админский интерфейс Sonata')]
    public function iShouldSeeTheSonataAdminInterface(): void
    {
        $text = $this->getSession()->getPage()->getText();
        if (!str_contains($text, 'Logout') && !str_contains($text, 'Sonata Admin')) {
            throw new \RuntimeException(
                \sprintf("Expected Sonata Admin UI markers (Logout / Sonata Admin).\n--- page text ---\n%s", $text)
            );
        }

        if (str_contains($text, 'Access Denied')) {
            throw new \RuntimeException('Sonata Admin UI is Access Denied, expected granted access.');
        }
    }

    #[Then('я не должен иметь доступ к админскому интерфейсу Sonata')]
    public function iShouldNotHaveAccessToSonataAdmin(): void
    {
        $text = $this->getSession()->getPage()->getText();
        $url = $this->getSession()->getCurrentUrl();

        $denied = str_contains($text, 'Access Denied')
            || str_contains($text, 'Access Denied.')
            || str_contains($text, '403 Forbidden');

        $stillOnLogin = str_contains($url, '/admin/login');

        if ($denied || $stillOnLogin) {
            return;
        }

        if (str_contains($url, '/admin/dashboard') && str_contains($text, 'Logout')) {
            throw new \RuntimeException(
                \sprintf(
                    "ROLE_USER unexpectedly reached Sonata Admin.\nURL: %s\n--- page text ---\n%s",
                    $url,
                    $text
                )
            );
        }

        throw new \RuntimeException(
            \sprintf(
                "Expected Access Denied, login page, or denied admin UI; got URL \"%s\".\n--- page text ---\n%s",
                $url,
                $text
            )
        );
    }
}
