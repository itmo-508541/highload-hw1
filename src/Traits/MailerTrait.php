<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\Mailer\MailerInterface;

trait MailerTrait
{
    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @required
     */
    public function setMailer(MailerInterface $mailer): void
    {
        $this->mailer = $mailer;
    }
}
