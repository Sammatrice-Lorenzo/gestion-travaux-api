<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

final class UniqueConstraintViolationListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof UniqueConstraintViolationException) {
            $response = new JsonResponse([
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Unprocessable Entity',
                'detail' => 'Cette adresse email est déjà utilisée.',
                'violations' => [[
                    'propertyPath' => 'email',
                    'message' => 'Cette adresse email est déjà utilisée.',
                ]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

            $event->setResponse($response);
        }
    }
}
