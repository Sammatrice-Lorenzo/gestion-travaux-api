<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use DateTime;
use App\Entity\User;
use App\Entity\Supplier;
use Codeception\Test\Unit;
use App\Entity\ProductInvoiceFile;
use App\Entity\SupplierReturnInvoiceFile;
use App\Service\SupplierReturnInvoiceLinkValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class SupplierReturnInvoiceLinkValidatorTest extends Unit
{
    private EntityManagerInterface&MockObject $entityManager;

    private SupplierReturnInvoiceLinkValidator $validator;

    protected function _before(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = new SupplierReturnInvoiceLinkValidator($this->entityManager);
    }

    public function testReturnsNullWhenNoLinkedInvoiceId(): void
    {
        $returnInvoice = $this->createReturnInvoice($this->createUser(1));

        $this->entityManager->expects($this->never())->method('getRepository');

        $this->assertNull($this->validator->resolve($returnInvoice, null, null));
    }

    public function testThrowsWhenLinkedInvoiceDoesNotExist(): void
    {
        $returnInvoice = $this->createReturnInvoice($this->createUser(1));
        $repository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => null,
        ]);
        $this->entityManager
            ->method('getRepository')
            ->with(ProductInvoiceFile::class)
            ->willReturn($repository)
        ;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Facture produit introuvable.');

        $this->validator->resolve($returnInvoice, 99, null);
    }

    public function testThrowsWhenLinkedInvoiceBelongsToAnotherUser(): void
    {
        $owner = $this->createUser(1);
        $otherUser = $this->createUser(2);
        $returnInvoice = $this->createReturnInvoice($owner);
        $linkedInvoice = $this->createProductInvoice($otherUser, null);

        $repository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => $linkedInvoice,
        ]);
        $this->entityManager
            ->method('getRepository')
            ->with(ProductInvoiceFile::class)
            ->willReturn($repository)
        ;

        $this->expectException(AccessDeniedHttpException::class);

        $this->validator->resolve($returnInvoice, 34, null);
    }

    public function testThrowsWhenSuppliersDoNotMatch(): void
    {
        $user = $this->createUser(1);
        $supplierA = $this->createSupplier($user, 10);
        $supplierB = $this->createSupplier($user, 20);
        $returnInvoice = $this->createReturnInvoice($user);
        $linkedInvoice = $this->createProductInvoice($user, $supplierB);

        $repository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => $linkedInvoice,
        ]);
        $this->entityManager
            ->method('getRepository')
            ->with(ProductInvoiceFile::class)
            ->willReturn($repository)
        ;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Le fournisseur de l\'avoir doit correspondre');

        $this->validator->resolve($returnInvoice, 34, $supplierA);
    }

    public function testReturnsLinkedInvoiceWhenValid(): void
    {
        $user = $this->createUser(1);
        $supplier = $this->createSupplier($user, 10);
        $returnInvoice = $this->createReturnInvoice($user);
        $linkedInvoice = $this->createProductInvoice($user, $supplier);

        $repository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => $linkedInvoice,
        ]);
        $this->entityManager
            ->method('getRepository')
            ->with(ProductInvoiceFile::class)
            ->willReturn($repository)
        ;

        $resolved = $this->validator->resolve($returnInvoice, 34, $supplier);

        $this->assertSame($linkedInvoice, $resolved);
    }

    private function createUser(int $id): User
    {
        $user = (new User())->setEmail("user{$id}@test.com");
        $this->setEntityId($user, $id);

        return $user;
    }

    private function createSupplier(User $user, int $id): Supplier
    {
        $supplier = (new Supplier())
            ->setName('Supplier')
            ->setAddress('1 rue Test')
            ->setCity('Paris')
            ->setCountry('France')
            ->setVatNumber('FR12345678901')
            ->setUser($user)
        ;
        $this->setEntityId($supplier, $id);

        return $supplier;
    }

    private function createReturnInvoice(User $user): SupplierReturnInvoiceFile
    {
        return (new SupplierReturnInvoiceFile())
            ->setUser($user)
            ->setName('return.pdf')
            ->setPath('return.pdf')
            ->setDate(new DateTime('2026-01-10'))
            ->setCreditAmount(10.0)
        ;
    }

    private function createProductInvoice(User $user, ?Supplier $supplier): ProductInvoiceFile
    {
        return (new ProductInvoiceFile())
            ->setUser($user)
            ->setName('invoice.pdf')
            ->setPath('invoice.pdf')
            ->setDate(new DateTime('2026-01-10'))
            ->setTotalAmount(50.0)
            ->setSupplier($supplier)
        ;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);
    }
}
