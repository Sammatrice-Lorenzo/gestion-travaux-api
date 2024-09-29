<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use App\Entity\ProductInvoiceFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

final readonly class ProductInvoiceService
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private Security $security
    ) {
    }

    /**
     * @param ProductInvoiceFile[] $productInvoiceFiles
     * @return void
     */
    private function createProductInvoice(Request $request, array &$productInvoiceFiles): void
    {
        $uploadedFiles = $request->files->all();
        $date = $request->request->get('date');

        /** @var User $user */
        $user = $this->entityManagerInterface->getRepository(User::class)->find($this->security->getUser()->getId());

        foreach ($uploadedFiles as $file) {
            $productInvoiceFile = (new ProductInvoiceFile())
                ->setUser($user)
                ->setName($file->getClientOriginalName())
                ->setDate(new DateTime($date))
                ->setFile($file)
            ;
            $this->entityManagerInterface->persist($productInvoiceFile);

            $productInvoiceFiles[] = $productInvoiceFile;
        }

        $this->entityManagerInterface->flush();
    }

    /**
     * @return ProductInvoiceFile[]
     */
    public function getProductInvoicesCreated(Request $request): array
    {
        $productInvoiceFiles = [];

        $this->createProductInvoice($request, $productInvoiceFiles);

        return $productInvoiceFiles;
    }

    /**
     * @param ProductInvoiceFile[] $productInvoice
     * @return string[]
     */
    public function getFiles(array $productInvoice): array
    {
        return array_map(function (ProductInvoiceFile $productInvoiceFile): string {
            return $productInvoiceFile->getPath();
        }, $productInvoice);
    }
}
