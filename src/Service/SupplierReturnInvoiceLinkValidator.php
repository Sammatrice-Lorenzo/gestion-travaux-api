<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Supplier;
use App\Entity\ProductInvoiceFile;
use App\Entity\SupplierReturnInvoiceFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class SupplierReturnInvoiceLinkValidator
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
    ) {}

    public function resolve(
        SupplierReturnInvoiceFile $returnInvoice,
        ?int $linkedProductInvoiceId,
        ?Supplier $supplier,
    ): ?ProductInvoiceFile {
        if (null === $linkedProductInvoiceId) {
            return null;
        }

        $linked = $this->entityManagerInterface
            ->getRepository(ProductInvoiceFile::class)
            ->find($linkedProductInvoiceId);

        if (!$linked instanceof ProductInvoiceFile) {
            throw new BadRequestHttpException('Facture produit introuvable.');
        }

        if ($linked->getUser()->getId() !== $returnInvoice->getUser()->getId()) {
            throw new AccessDeniedHttpException('Cette facture ne vous appartient pas.');
        }

        $linkedSupplier = $linked->getSupplier();

        $isSameSupplier = $supplier?->getId() === $linkedSupplier?->getId();
        $supplierAndLinkedSupplierNotNull = null !== $supplier && null !== $linkedSupplier;
        if ($supplierAndLinkedSupplierNotNull && !$isSameSupplier) {
            throw new BadRequestHttpException('Le fournisseur de l\'avoir doit correspondre à celui de la facture liée.', );
        }

        return $linked;
    }
}
