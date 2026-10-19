<?php

declare(strict_types=1);

namespace App\Service\InvoicingPlatform;

use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;
use App\Entity\Supplier;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Intl\Countries;

use function Symfony\Component\String\u;

/**
 * Retrouve le fournisseur d'une facture de la plateforme agréée, ou le crée (« trouve ou crée »).
 * Ordre : identifiant plateforme, n° de TVA, nom normalisé, puis création. Une valeur déjà saisie n'est jamais écrasée.
 */
final readonly class SupplierMatcher
{
    private const int NAME_MAX_LENGTH = 255;

    private const int VAT_NUMBER_MAX_LENGTH = 50;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Charge une fois les fournisseurs de l'utilisateur, à réutiliser pour toutes les factures d'une synchro.
     */
    public function createLookup(User $user): SupplierLookup
    {
        return new SupplierLookup($this->entityManager->getRepository(Supplier::class)->findBy(['user' => $user]));
    }

    /**
     * Le fournisseur créé est persisté mais pas flushé : l'appelant flushe avec la facture.
     *
     * @return null|Supplier null si la facture ne permet ni de retrouver ni de nommer un fournisseur
     */
    public function findOrCreate(User $user, PlatformSupplierInvoice $invoice, SupplierLookup $lookup): ?Supplier
    {
        $supplier = $lookup->find($invoice);

        if (null === $supplier) {
            if (null === $invoice->supplierName) {
                return null;
            }

            $supplier = (new Supplier())->setUser($user)->setName($this->truncate($invoice->supplierName, self::NAME_MAX_LENGTH));
            $this->entityManager->persist($supplier);
        }

        $this->completeMissingFields($supplier, $invoice);
        $lookup->add($supplier);

        return $supplier;
    }

    /**
     * Ne remplit que les champs vides : l'identifiant plateforme, la TVA et l'adresse saisis à la main sont conservés.
     */
    private function completeMissingFields(Supplier $supplier, PlatformSupplierInvoice $invoice): void
    {
        if (null === $supplier->getQontoSupplierId() && null !== $invoice->supplierId) {
            $supplier->setQontoSupplierId($this->truncate($invoice->supplierId, self::NAME_MAX_LENGTH));
        }

        if (null === $supplier->getVatNumber() && null !== $invoice->vatNumber) {
            $supplier->setVatNumber($this->truncate($invoice->vatNumber, self::VAT_NUMBER_MAX_LENGTH));
        }

        if (null === $supplier->getAddress() && null !== $invoice->supplierStreetAddress) {
            $supplier->setAddress($this->truncate($invoice->supplierStreetAddress, self::NAME_MAX_LENGTH));
        }

        $city = trim(($invoice->supplierZipCode ?? '') . ' ' . ($invoice->supplierCity ?? ''));
        if (null === $supplier->getCity() && '' !== $city) {
            $supplier->setCity($this->truncate($city, self::NAME_MAX_LENGTH));
        }

        if (null === $supplier->getCountry() && null !== $invoice->supplierCountryCode) {
            $supplier->setCountry($this->truncate($this->countryName($invoice->supplierCountryCode), self::NAME_MAX_LENGTH));
        }
    }

    private function countryName(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode);

        return Countries::exists($countryCode) ? Countries::getName($countryCode, 'fr') : $countryCode;
    }

    /**
     * Les colonnes sont limitées en base : une valeur trop longue ferait échouer tout l'import.
     */
    private function truncate(string $value, int $maxLength): string
    {
        return u($value)->truncate($maxLength)->toString();
    }
}
