<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\InvoicingPlatform;

use App\Entity\Supplier;
use App\Entity\User;
use App\Service\InvoicingPlatform\SupplierMatcher;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\Fake\FakeInvoicingPlatformClient;
use App\Tests\Support\UnitTester;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;

final class SupplierMatcherTest extends Unit
{
    protected UnitTester $tester;

    private EntityManagerInterface $entityManager;

    private SupplierMatcher $matcher;

    private User $user;

    protected function _before(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->tester->grabService('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;
        $this->matcher = new SupplierMatcher($this->entityManager);

        /** @var User $user */
        $user = $this->tester->grabEntityFromRepository(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
    }

    public function testFindsByPlatformSupplierId(): void
    {
        $existing = $this->createSupplier('Matcher Par Id')->setQontoSupplierId('qonto-sup-1');
        $this->entityManager->flush();

        $supplier = $this->matcher->findOrCreate($this->user, FakeInvoicingPlatformClient::invoice('m1', supplierId: 'qonto-sup-1', supplierName: 'Autre nom'));

        $this->assertSame($existing, $supplier);
    }

    public function testFindsByVatNumberAndStoresPlatformId(): void
    {
        $existing = $this->createSupplier('Matcher Par TVA')->setVatNumber('FR 32 123 456 789');
        $this->entityManager->flush();

        $supplier = $this->matcher->findOrCreate($this->user, FakeInvoicingPlatformClient::invoice('m2', supplierId: 'qonto-sup-2', vatNumber: 'fr32123456789', supplierName: 'Nom différent'));

        $this->assertSame($existing, $supplier);
        $this->assertSame('qonto-sup-2', $existing->getQontoSupplierId());
        $this->assertSame('FR 32 123 456 789', $existing->getVatNumber());
    }

    public function testFindsByNormalizedName(): void
    {
        $existing = $this->createSupplier('Leroy-Merlin Évry Matcher');
        $this->entityManager->flush();

        $supplier = $this->matcher->findOrCreate($this->user, FakeInvoicingPlatformClient::invoice('m3', supplierName: '  LEROY MERLIN evry   matcher '));

        $this->assertSame($existing, $supplier);
    }

    public function testCreatesUnknownSupplierWithAvailableData(): void
    {
        $supplier = $this->matcher->findOrCreate($this->user, FakeInvoicingPlatformClient::invoice(
            'm4',
            supplierId: 'qonto-sup-4',
            supplierName: 'Nouveau Fournisseur Matcher',
            vatNumber: 'FR99999999999',
            supplierStreetAddress: '1 rue du Test',
            supplierZipCode: '75001',
            supplierCity: 'Paris',
            supplierCountryCode: 'fr',
        ));

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertNull($supplier->getId(), 'Persisté mais pas encore flushé.');
        $this->assertTrue($this->entityManager->contains($supplier));
        $this->assertSame($this->user, $supplier->getUser());
        $this->assertSame('Nouveau Fournisseur Matcher', $supplier->getName());
        $this->assertSame('qonto-sup-4', $supplier->getQontoSupplierId());
        $this->assertSame('FR99999999999', $supplier->getVatNumber());
        $this->assertSame('1 rue du Test', $supplier->getAddress());
        $this->assertSame('75001 Paris', $supplier->getCity());
        $this->assertSame('France', $supplier->getCountry());
    }

    public function testCreatesSupplierWithOnlyName(): void
    {
        $supplier = $this->matcher->findOrCreate($this->user, FakeInvoicingPlatformClient::invoice('m5', supplierName: 'Ticket Sans Adresse Matcher'));

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertNull($supplier->getAddress());
        $this->assertNull($supplier->getCity());
        $this->assertNull($supplier->getCountry());
    }

    public function testNeverOverwritesValuesEnteredByUser(): void
    {
        $existing = $this->createSupplier('Fournisseur Saisi Matcher')
            ->setAddress('Mon adresse')
            ->setVatNumber('FR11111111111')
            ->setQontoSupplierId('qonto-autre')
        ;
        $this->entityManager->flush();

        $supplier = $this->matcher->findOrCreate($this->user, FakeInvoicingPlatformClient::invoice(
            'm6',
            supplierId: 'qonto-sup-6',
            supplierName: 'fournisseur saisi matcher',
            vatNumber: 'FR22222222222',
            supplierStreetAddress: 'Adresse Qonto',
            supplierCity: 'Lyon',
        ));

        $this->assertSame($existing, $supplier);
        $this->assertSame('Mon adresse', $existing->getAddress());
        $this->assertSame('FR11111111111', $existing->getVatNumber());
        $this->assertSame('qonto-autre', $existing->getQontoSupplierId());
        $this->assertSame('Lyon', $existing->getCity(), 'Un champ vide est complété.');
    }

    public function testReturnsNullWithoutSupplierData(): void
    {
        $this->assertNull($this->matcher->findOrCreate($this->user, FakeInvoicingPlatformClient::invoice('m7')));
    }

    public function testNormalizers(): void
    {
        $this->assertSame('leroy merlin evry', SupplierMatcher::normalizeName('  Leroy-Merlin   ÉVRY '));
        $this->assertSame('FR32123456789', SupplierMatcher::normalizeVatNumber('fr 32.123 456-789'));
    }

    private function createSupplier(string $name): Supplier
    {
        $supplier = (new Supplier())->setUser($this->user)->setName($name);
        $this->entityManager->persist($supplier);

        return $supplier;
    }
}
