<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait MailerTrait
{
    protected MailerInterface $mailer;

    #[Required]
    public function setMailer(MailerInterface $mailer): void
    {
        $this->mailer = $mailer;
    }
}
