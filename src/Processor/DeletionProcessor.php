<?php

namespace App\Processor;

use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class DeletionProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManagerInterface
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        try {
            $this->entityManagerInterface->remove($data);
            $this->entityManagerInterface->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new UnprocessableEntityHttpException('Impossible de supprimer cette ressource : elle est encore utilisée ailleurs.');
        }

        return null;
    }
}
