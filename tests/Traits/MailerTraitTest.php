<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Traits\MailerTrait;
use Symfony\Component\Mailer\MailerInterface;

final class MailerTraitTest extends RequiredSetterTestCase
{
    protected function setUp(): void
    {
        $this->object = new class {
            use MailerTrait;
        };
        $this->setterName = 'setMailer';
        $this->propertyClass = MailerInterface::class;
        $this->propertyName = 'mailer';
    }
}
