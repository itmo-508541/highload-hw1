<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Then;
use Behat\Step\When;

final class HomepageContext extends RawMinkContext
{
    #[When('я открываю главную страницу в браузере')]
    public function iOpenTheHomepageInTheBrowser(): void
    {
        $this->visitPath('/');
    }

    #[Then('текущий URL должен заканчиваться на :suffix')]
    public function currentUrlShouldEndWith(string $suffix): void
    {
        $url = $this->getSession()->getCurrentUrl();
        if (!str_ends_with($url, $suffix)) {
            throw new \RuntimeException(\sprintf('Expected URL to end with "%s", got "%s".', $suffix, $url));
        }
    }
}
