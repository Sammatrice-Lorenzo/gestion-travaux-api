<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SupplierReturnInvoiceFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vich\UploaderBundle\Storage\StorageInterface;

final readonly class SupplierReturnInvoiceFileResolver
{
    public function __construct(
        private ParameterBagInterface $parameterBagInterface,
        private StorageInterface $storage,
    ) {}

    public function resolveAbsolutePath(SupplierReturnInvoiceFile $invoice): string
    {
        $baseDir = rtrim((string) $this->parameterBagInterface->get('supplier_return_invoices'), '/\\');

        $vichPath = $this->storage->resolvePath($invoice, 'file');
        if (null !== $vichPath && is_file($vichPath)) {
            return $vichPath;
        }

        $storedPath = $invoice->getPath();
        if (null === $storedPath || '' === $storedPath) {
            throw new NotFoundHttpException('Fichier introuvable.');
        }

        $normalizedStoredPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath);

        $candidates = [
            $baseDir . DIRECTORY_SEPARATOR . $normalizedStoredPath,
            $baseDir . DIRECTORY_SEPARATOR . basename($normalizedStoredPath),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new NotFoundHttpException(sprintf('Le fichier "%s" est introuvable sur le serveur.', $storedPath));
    }

    public function resolveRelativePath(SupplierReturnInvoiceFile $invoice): string
    {
        $absolutePath = $this->resolveAbsolutePath($invoice);
        $baseDir = rtrim((string) $this->parameterBagInterface->get('supplier_return_invoices'), '/\\') . DIRECTORY_SEPARATOR;

        return str_replace($baseDir, '', $absolutePath);
    }
}
