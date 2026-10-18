<?php

declare(strict_types=1);

namespace App\Service\InvoicingPlatform;

use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;
use App\Entity\Supplier;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\String\UnicodeString;

/**
 * Retrouve le fournisseur d'une facture de la plateforme agréée, ou le crée (« trouve ou crée »).
 * Ordre : identifiant plateforme, n° de TVA, nom normalisé, puis création. Une valeur déjà saisie n'est jamais écrasée.
 */
final readonly class SupplierMatcher
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Le fournisseur créé est persisté mais pas flushé : l'appelant flushe avec la facture.
     *
     * @return null|Supplier null si la facture ne permet ni de retrouver ni de nommer un fournisseur
     */
    public function findOrCreate(User $user, PlatformSupplierInvoice $invoice): ?Supplier
    {
        $repository = $this->entityManager->getRepository(Supplier::class);

        if (null !== $invoice->supplierId) {
            $supplier = $repository->findOneBy(['user' => $user, 'qontoSupplierId' => $invoice->supplierId]);
            if (null !== $supplier) {
                return $supplier;
            }
        }

        $userSuppliers = $repository->findBy(['user' => $user]);
        $supplier = $this->findByVatNumber($userSuppliers, $invoice->vatNumber)
            ?? $this->findByName($userSuppliers, $invoice->supplierName);

        if (null !== $supplier) {
            return $this->completeMissingFields($supplier, $invoice);
        }

        if (null === $invoice->supplierName) {
            return null;
        }

        $supplier = $this->completeMissingFields(
            (new Supplier())->setUser($user)->setName($invoice->supplierName),
            $invoice,
        );
        $this->entityManager->persist($supplier);

        return $supplier;
    }

    public static function normalizeName(string $name): string
    {
        return (new UnicodeString($name))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->collapseWhitespace()
            ->trim()
            ->toString()
        ;
    }

    public static function normalizeVatNumber(string $vatNumber): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $vatNumber));
    }

    /**
     * @param Supplier[] $suppliers
     */
    private function findByVatNumber(array $suppliers, ?string $vatNumber): ?Supplier
    {
        if (null === $vatNumber || '' === self::normalizeVatNumber($vatNumber)) {
            return null;
        }

        $normalized = self::normalizeVatNumber($vatNumber);
        foreach ($suppliers as $supplier) {
            if (null !== $supplier->getVatNumber() && self::normalizeVatNumber($supplier->getVatNumber()) === $normalized) {
                return $supplier;
            }
        }

        return null;
    }

    /**
     * @param Supplier[] $suppliers
     */
    private function findByName(array $suppliers, ?string $name): ?Supplier
    {
        if (null === $name || '' === self::normalizeName($name)) {
            return null;
        }

        $normalized = self::normalizeName($name);
        foreach ($suppliers as $supplier) {
            if (self::normalizeName($supplier->getName()) === $normalized) {
                return $supplier;
            }
        }

        return null;
    }

    /**
     * Ne remplit que les champs vides : l'identifiant plateforme, la TVA et l'adresse saisis à la main sont conservés.
     */
    private function completeMissingFields(Supplier $supplier, PlatformSupplierInvoice $invoice): Supplier
    {
        if (null === $supplier->getQontoSupplierId() && null !== $invoice->supplierId) {
            $supplier->setQontoSupplierId($invoice->supplierId);
        }

        if (null === $supplier->getVatNumber() && null !== $invoice->vatNumber) {
            $supplier->setVatNumber($invoice->vatNumber);
        }

        if (null === $supplier->getAddress() && null !== $invoice->supplierStreetAddress) {
            $supplier->setAddress($invoice->supplierStreetAddress);
        }

        $city = trim(($invoice->supplierZipCode ?? '') . ' ' . ($invoice->supplierCity ?? ''));
        if (null === $supplier->getCity() && '' !== $city) {
            $supplier->setCity($city);
        }

        if (null === $supplier->getCountry() && null !== $invoice->supplierCountryCode) {
            $supplier->setCountry($this->countryName($invoice->supplierCountryCode));
        }

        return $supplier;
    }

    private function countryName(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode);

        return Countries::exists($countryCode) ? Countries::getName($countryCode, 'fr') : $countryCode;
    }
}
