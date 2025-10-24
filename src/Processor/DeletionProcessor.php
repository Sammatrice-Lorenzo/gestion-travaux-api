<?php

namespace App\Processor;

use Doctrine\ORM\Mapping\Entity;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<Entity, mixed>
 */
final class DeletionProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManagerInterface
    ) {}

    /**
     * @param Entity $data
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @throws UnprocessableEntityHttpException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
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
