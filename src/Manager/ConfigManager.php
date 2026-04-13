<?php

declare(strict_types=1);

namespace App\Manager;

use App\Repository\ConfigRepositoryInterface;
use Symfony\Contracts\Service\Attribute\Required;

class ConfigManager
{
    private ConfigRepositoryInterface $repository;

    public function getValue(array $params, string $suffix, mixed $default = null): ?string
    {
        return $this->repository->getValue($params, $suffix, $default);
    }

    public function getTemplatedValue(array $params, string $suffix, array $variables): ?string
    {
        $template = $this->repository->getValue($params, $suffix);
        if (null !== $template) {
            foreach ($variables as $key => $value) {
                $template = str_replace(\sprintf('{{%s}}', $key), $value, $template);
            }
        }

        return $template;
    }

    public function setValue(array $params, string $suffix, mixed $value): void
    {
        $this->repository->setValue($params, $suffix, $value);
    }

    #[Required]
    public function setConfigRepository(ConfigRepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }
}
