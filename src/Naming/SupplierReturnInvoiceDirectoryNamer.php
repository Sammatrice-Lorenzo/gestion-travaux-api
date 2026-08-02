<?php

declare(strict_types=1);

namespace App\Naming;

use App\Entity\SupplierReturnInvoiceFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * @implements DirectoryNamerInterface<SupplierReturnInvoiceFile>
 */
final readonly class SupplierReturnInvoiceDirectoryNamer implements DirectoryNamerInterface
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {}

    /**
     * @param array<string, mixed>|SupplierReturnInvoiceFile $object
     */
    public function directoryName(object|array $object, PropertyMapping $mapping): string
    {
        if (!$object instanceof SupplierReturnInvoiceFile) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', SupplierReturnInvoiceFile::class, $this->describeInvalidType($object), ));
        }

        $date = $object->getDate();
        $supplierSlug = $this->slugger->slug($object->getSupplier()?->getName() ?? 'sans-fournisseur')->lower()->toString();

        return sprintf('%s/%s/%s', $date->format('Y'), $date->format('m'), $supplierSlug);
    }

    /**
     * @param array<string, mixed>|object $object
     */
    private function describeInvalidType(object|array $object): string
    {
        return is_object($object) ? $object::class : 'array';
    }
}
