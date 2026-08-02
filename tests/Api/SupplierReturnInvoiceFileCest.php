<?php

declare(strict_types=1);

namespace App\Tests\Api;

use DateTime;
use ZipArchive;
use App\Entity\User;
use App\Entity\Supplier;
use App\Helper\DateFormatHelper;
use App\Tests\Support\ApiTester;
use App\Tests\Enum\TestPdfFileEnum;
use Codeception\Attribute\Depends;
use App\Tests\Enum\UserFixturesEnum;
use App\Entity\SupplierReturnInvoiceFile;

final class SupplierReturnInvoiceFileCest
{
    private const string DIRECTORY_FILES = 'supplier_return_invoices';

    private const string URL_API = '/api/supplier_return_invoice_files';

    private User $user;

    private SupplierReturnInvoiceFile $supplierReturnInvoiceFile;

    private DateTime $date;

    public function _before(ApiTester $I): void
    {
        /** @var User $user */
        $user = $I->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
        $this->date = new DateTime();

        /** @var ?SupplierReturnInvoiceFile $supplierReturnInvoiceFile */
        $supplierReturnInvoiceFile = $I->grabEntity(
            SupplierReturnInvoiceFile::class,
            ['user' => $this->user],
            ['id' => 'ASC'],
        );
        if ($supplierReturnInvoiceFile) {
            $this->supplierReturnInvoiceFile = $supplierReturnInvoiceFile;
        }

        $I->loginAs();
    }

    public function testAddSupplierReturnInvoiceFile(ApiTester $I): void
    {
        $response = $this->uploadSupplierReturnInvoice($I);
        $I->seeResponseCodeIsSuccessful();
        $data = json_decode($response, true);
        $fileName = $data[0]['path'];

        $I->assertFileExistsInUploadDirectory(self::DIRECTORY_FILES, $fileName);
        $I->seeResponseContainsJson([
            'name' => TestPdfFileEnum::INVOICE_TEMPLATE->value,
        ]);
    }

    #[Depends('testAddSupplierReturnInvoiceFile')]
    public function testAddSupplierReturnInvoiceFileWithSupplier(ApiTester $I): void
    {
        /** @var Supplier $supplier */
        $supplier = $I->grabEntity(Supplier::class, ['user' => $this->user]);

        $response = $this->uploadSupplierReturnInvoice($I, ['supplierId' => $supplier->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $data = json_decode($response, true);

        $I->assertFileExistsInUploadDirectory(self::DIRECTORY_FILES, $data[0]['path']);
        $I->seeResponseContainsJson([
            'supplier' => [
                'id' => $supplier->getId(),
                'name' => $supplier->getName(),
            ],
        ]);
    }

    #[Depends('testAddSupplierReturnInvoiceFileWithSupplier')]
    public function testGetCollectionSupplierReturnInvoiceFile(ApiTester $I): void
    {
        $I->sendGet(self::URL_API . "?date={$this->date->format(DateFormatHelper::DEFAULT_FORMAT)}");
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson([
            'name' => TestPdfFileEnum::INVOICE_TEMPLATE->value,
        ]);
    }

    #[Depends('testGetCollectionSupplierReturnInvoiceFile')]
    public function testGetFileDownload(ApiTester $I): void
    {
        $fileName = 'test.pdf';
        $I->removeFile($fileName);
        $I->sendGet(self::URL_API . "/{$this->supplierReturnInvoiceFile->getId()}/download");
        $I->seeResponseCodeIsSuccessful();
        $file = $I->grabResponse();

        file_put_contents($fileName, $file);
        $testFile = "./{$fileName}";

        $I->assertFileEquals($testFile, TestPdfFileEnum::path());
        $I->removeFile($fileName);
    }

    #[Depends('testGetFileDownload')]
    public function testPostDownloadZip(ApiTester $I): void
    {
        $fileName = 'test.zip';
        $I->removeFile($fileName);
        $I->sendPost(self::URL_API . '_download_zip', [
            'ids' => [
                $this->supplierReturnInvoiceFile->getId(),
            ],
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeHttpHeader('Content-Type', 'application/zip');

        $file = $I->grabResponse();

        file_put_contents($fileName, $file);
        $testFile = "./{$fileName}";
        $this->assertIsAZip($I, $testFile);
        $I->removeFile($fileName);
    }

    #[Depends('testPostDownloadZip')]
    public function testPutSupplierReturnInvoiceFile(ApiTester $I): void
    {
        $parameters = $this->getPutParameters('Test supplier return file');

        $I->sendJsonPut(self::URL_API . "/{$this->supplierReturnInvoiceFile->getId()}", $parameters);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($this->getResponseParameters('Test supplier return file'));
    }

    #[Depends('testPutSupplierReturnInvoiceFile')]
    public function testPutSupplierReturnInvoiceFileWithSupplier(ApiTester $I): void
    {
        /** @var Supplier $supplier */
        $supplier = $I->grabEntity(Supplier::class, ['user' => $this->user]);

        $parameters = $this->getPutParameters('Test supplier return with supplier');
        $parameters['supplierId'] = $supplier->getId();

        $I->sendJsonPut(self::URL_API . "/{$this->supplierReturnInvoiceFile->getId()}", $parameters);
        $I->seeResponseCodeIsSuccessful();

        $expected = $this->getResponseParameters('Test supplier return with supplier');
        $expected['supplier'] = ['id' => $supplier->getId(), 'name' => $supplier->getName()];

        $I->seeResponseContainsJson($expected);
    }

    #[Depends('testPutSupplierReturnInvoiceFileWithSupplier')]
    public function testPutSupplierReturnInvoiceFileWithLinkedProductInvoice(ApiTester $I): void
    {
        $productInvoiceId = $this->uploadProductInvoiceFile($I);

        $parameters = $this->getPutParameters('Test supplier return linked invoice');
        $parameters['linkedProductInvoiceId'] = $productInvoiceId;

        $I->sendJsonPut(self::URL_API . "/{$this->supplierReturnInvoiceFile->getId()}", $parameters);
        $I->seeResponseCodeIsSuccessful();

        $expected = $this->getResponseParameters('Test supplier return linked invoice');
        $expected['linkedProductInvoice'] = ['id' => $productInvoiceId];

        $I->seeResponseContainsJson($expected);
    }

    #[Depends('testPutSupplierReturnInvoiceFileWithLinkedProductInvoice')]
    public function testProductInvoiceCollectionIncludesLinkedSupplierReturns(ApiTester $I): void
    {
        $I->sendGet('/api/product_invoice_files?date=' . $this->date->format(DateFormatHelper::DEFAULT_FORMAT));
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson([
            'linkedSupplierReturns' => [
                [
                    'name' => 'Test supplier return linked invoice',
                ],
            ],
        ]);
    }

    #[Depends('testProductInvoiceCollectionIncludesLinkedSupplierReturns')]
    public function testDeleteSupplierReturnInvoiceFile(ApiTester $I): void
    {
        $storedPath = $this->supplierReturnInvoiceFile->getPath();

        $I->sendDelete(self::URL_API . "/{$this->supplierReturnInvoiceFile->getId()}");
        $I->seeResponseCodeIsSuccessful();
        $I->assertFileNotExistsInUploadDirectory(self::DIRECTORY_FILES, (string) $storedPath);
    }

    /**
     * @param array<string, null|int|string> $extraFields
     */
    private function uploadSupplierReturnInvoice(ApiTester $I, array $extraFields = []): string
    {
        $I->deleteHeader('Content-Type');

        $response = $I->sendPost(self::URL_API, array_merge([
            'date' => $this->date->format(DateFormatHelper::DEFAULT_FORMAT),
        ], $extraFields), [
            'files[]' => TestPdfFileEnum::uploadPayload(),
        ]);
        $this->resetJsonContentType($I);

        return $response;
    }

    private function resetJsonContentType(ApiTester $I): void
    {
        $I->deleteHeader('Content-Type');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    private function uploadProductInvoiceFile(ApiTester $I): int
    {
        $I->deleteHeader('Content-Type');
        $response = $I->sendPost('/api/product_invoice_files', [
            'date' => $this->date->format(DateFormatHelper::DEFAULT_FORMAT),
        ], [
            'files[]' => TestPdfFileEnum::uploadPayload(),
        ]);
        $I->seeResponseCodeIsSuccessful();
        $this->resetJsonContentType($I);
        $data = json_decode($response, true);

        return (int) $data[0]['id'];
    }

    /**
     * @return array<string, float|string>
     */
    private function getResponseParameters(string $name): array
    {
        return [
            'name' => $name,
            'creditAmount' => 127.50,
        ];
    }

    /**
     * @return array<string, null|float|string>
     */
    private function getPutParameters(string $name): array
    {
        return [
            'name' => $name,
            'date' => $this->date->format(DateFormatHelper::DEFAULT_FORMAT),
            'creditAmount' => 127.50,
            'supplierId' => null,
            'linkedProductInvoiceId' => null,
        ];
    }

    private function assertIsAZip(ApiTester $I, string $file): void
    {
        $zip = new ZipArchive();
        $I->assertTrue(true === $zip->open($file));
        $I->assertEquals($zip->count(), 1);
    }
}
