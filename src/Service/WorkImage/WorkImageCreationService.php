<?php

namespace App\Service\WorkImage;

use DateTimeImmutable;
use App\Entity\WorkImage;
use App\Repository\WorkRepository;
use App\Dto\WorkImageCreationInput;
use App\Helper\ImageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\InvalidEntityRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

final readonly class WorkImageCreationService
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private WorkRepository $workRepository,
        private ParameterBagInterface $parameterBagInterface
    ) {}

    /**
     * @param WorkImage[] $workImages
     */
    private function createProductInvoice(WorkImageCreationInput $workImageCreationInput, array &$workImages): void
    {
        $uploadedImages = $workImageCreationInput->images;
        $work = $this->workRepository->find($workImageCreationInput->workId);
        if (!$work) {
            throw new InvalidEntityRepository();
        }

        /** @var string $path */
        $path = $this->parameterBagInterface->get('work_images');
        foreach ($uploadedImages as $image) {
            $workImage = (new WorkImage())
                ->setImageName($image->getClientOriginalName())
                ->setImageFile($image)
                ->setUpdatedAt(new DateTimeImmutable())
                ->setWork($work)
            ;
            $this->entityManagerInterface->persist($workImage);
            
            ImageHelper::compress($workImage->getImageName(), $path, 75);

            $workImages[] = $workImage;
        }

        $this->entityManagerInterface->flush();
    }

    /**
     * @return WorkImage[]
     */
    public function getWorkImagesCreated(WorkImageCreationInput $workImageCreationInput): array
    {
        $workImages = [];
        $this->createProductInvoice($workImageCreationInput, $workImages);

        return $workImages;
    }
}
