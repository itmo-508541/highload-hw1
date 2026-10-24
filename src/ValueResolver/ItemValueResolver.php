<?php

declare(strict_types=1);

namespace App\ValueResolver;

use App\Model\Rest\ItemValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Тело `POST /items` и `PUT /items/{id}`: JSON-объект ровно с ключом `value`
 * и скалярным значением. JSON уже разобран FOSRest `body_listener`'ом,
 * невалидный JSON он отклоняет сам.
 *
 * `Content-Type` проверяется здесь: тела форм `body_listener` пропускает, и Symfony
 * раскладывает их в тот же `$request->request`.
 *
 * Работает до вызова контроллера: невалидное тело даёт 400, не обращаясь к хранилищу.
 */
final class ItemValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (ItemValue::class !== $argument->getType()) {
            return [];
        }

        // Не `getContentTypeFormat()`: формат `json` включает и `application/x-json`.
        $mediaType = strtolower(trim(explode(';', (string) $request->headers->get('Content-Type'))[0]));
        if ('application/json' !== $mediaType) {
            throw new BadRequestHttpException('Body must be sent with "Content-Type: application/json".');
        }

        $body = $request->request->all();
        if (['value'] !== array_keys($body)) {
            throw new BadRequestHttpException('Body must be a JSON object with exactly one key "value".');
        }

        $value = $body['value'];
        if (!\is_scalar($value)) {
            throw new BadRequestHttpException('"value" must be a string, a number or a boolean.');
        }

        // `1e400` декодируется в INF, а его не закодировать обратно в JSON при записи.
        if (\is_float($value) && !is_finite($value)) {
            throw new BadRequestHttpException('"value" must be a finite number.');
        }

        return [new ItemValue($value)];
    }
}
