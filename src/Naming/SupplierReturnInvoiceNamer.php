<?php

declare(strict_types=1);

namespace App\Naming;

use App\Entity\SupplierReturnInvoiceFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/**
 * @implements NamerInterface<SupplierReturnInvoiceFile>
 */
final readonly class SupplierReturnInvoiceNamer implements NamerInterface
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {}

    public function name(object $object, PropertyMapping $mapping): string
    {
        if (!$object instanceof SupplierReturnInvoiceFile) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', SupplierReturnInvoiceFile::class, $object::class, ));
        }

        $extension = $object->getFile()?->guessExtension() ?? 'pdf';
        $baseName = pathinfo($object->getName() ?: 'document', PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($baseName)->lower()->toString();
        $unique = substr(str_replace('.', '', uniqid('', true)), -12);

        return sprintf('%s_%s.%s', $safeName, $unique, $extension);
    }
}
