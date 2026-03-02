<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Service\Attribute\Required;
use App\Entity\Config;
use App\Repository\ConfigRepository;

class LoaderConfig
{
    private ConfigRepository $configRepository;

    public function getValue(array $params, string $suffix, $default = null): ?string
    {
        $key = $this->key($params, $suffix);
        $record = $this->configRepository->find($key);

        return null === $record ? $default : $record->getValue();
    }

    public function getTemplatedValue(array $params, string $suffix, array $variables): ?string
    {
        $template = $this->getValue($params, $suffix);
        if (null !== $template) {
            foreach ($variables as $key => $value) {
                $template = str_replace(sprintf('{{%s}}', $key), $value, $template);
            }
        }

        return $template;
    }

    public function setValue(array $params, string $suffix, $value): void
    {
        $key = $this->key($params, $suffix);
        $record = $this->configRepository->find($key);

        if (null === $record) {
            $record = new Config();

            $record->setId($key);
        }

        $record->setValue((string) $value);

        $this->configRepository->save($record);
    }

    #[Required]
    public function setConfigRepository(ConfigRepository $configRepository): void
    {
        $this->configRepository = $configRepository;
    }

    private function key(array $params, string $suffix): string
    {
        return implode('.', $params).'.'.$suffix;
    }
}
