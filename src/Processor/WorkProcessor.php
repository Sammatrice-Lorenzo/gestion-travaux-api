<?php

namespace App\Processor;

use App\Entity\Work;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<Work, Work|void>
 */
final class WorkProcessor implements ProcessorInterface
{
    public function __construct(
        /**
         * @var ProcessorInterface<Work, void|Work>
         */
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $processorInterface,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return void|Work
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Work) {
            return $this->processorInterface->process($data, $operation, $uriVariables, $context);
        }

        if ($operation instanceof Delete) {
            $data->getInvoice();
            $this->entityManager->remove($data->getInvoice());
            $data->setInvoice(null);
            $this->entityManager->flush();
        }

        return $data;
    }
}
