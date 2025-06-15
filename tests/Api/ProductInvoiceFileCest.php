<?php

declare(strict_types=1);

namespace App\Tests\Api;

use DateTime;
use ZipArchive;
use App\Entity\User;
use App\Helper\DateFormatHelper;
use App\Tests\Support\ApiTester;
use App\Entity\ProductInvoiceFile;
use Codeception\Attribute\Depends;
use App\Tests\Enum\UserFixturesEnum;

final class ProductInvoiceFileCest
{
    private const string FILE_NAME = 'InvoiceTemplate.pdf';

    private const string DIRECTORY_FILES = 'products_invoice';

    private User $user;

    private ProductInvoiceFile $productInvoiceFile;

    private DateTime $date;

    private const string URL_API = '/api/product_invoice_files';

    public function _before(ApiTester $I): void
    {
        /** @var User $user */
        $user = $I->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
        $this->date = new DateTime();

        /**
         * @var ?ProductInvoiceFile $productInvoiceFile
         */
        $productInvoiceFile = $I->grabEntity(ProductInvoiceFile::class, ['user' => $this->user]);
        if ($productInvoiceFile) {
            $this->productInvoiceFile = $productInvoiceFile;
        }

        $I->loginAs();
    }

    public function testAddProductInvoiceFile(ApiTester $I): void
    {
        $filePath = codecept_data_dir(self::FILE_NAME);

        $I->haveHttpHeader('Content-Type', '');
        $response = $I->sendPost(self::URL_API, [
            'date' => (new DateTime())->format(DateFormatHelper::DEFAULT_FORMAT),
        ], [
            'files[]' => [
                'name' => self::FILE_NAME,
                'type' => 'application/pdf',
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($filePath),
                'tmp_name' => $filePath,
            ],
        ]);
        $I->seeResponseCodeIsSuccessful();
        $data = json_decode($response, true);
        $fileName = $data[0]['path'];

        $I->assertFileIsUploaded(self::DIRECTORY_FILES, $fileName);
    }

    #[Depends('testAddProductInvoiceFile')]
    public function testGetCollectionProductInvoiceFile(ApiTester $I): void
    {
        $I->sendGet(self::URL_API . "?date={$this->date->format(DateFormatHelper::DEFAULT_FORMAT)}");
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson([
            'name' => self::FILE_NAME,
        ]);
    }

    #[Depends('testGetCollectionProductInvoiceFile')]
    public function testGetFileDownload(ApiTester $I): void
    {
        $fileName = 'test.pdf';
        $I->removeFile($fileName);
        $I->sendGet(self::URL_API . "/{$this->productInvoiceFile->getId()}/download");
        $I->seeResponseCodeIsSuccessful();
        $file = $I->grabResponse();

        file_put_contents($fileName, $file);
        $testFile = "./{$fileName}";

        $I->assertFileEquals($testFile, codecept_data_dir(self::FILE_NAME));
        $I->removeFile($fileName);
    }

    #[Depends('testGetFileDownload')]
    public function testPostDownloadZip(ApiTester $I): void
    {
        $fileName = 'test.zip';
        $I->removeFile($fileName);
        $I->sendPost(self::URL_API . '_download_zip', [
            'ids' => [
                $this->productInvoiceFile->getId(),
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
    public function testPutProductInvoiceFile(ApiTester $I): void
    {
        $parameters = $this->getPameters('Test product invoice file');
        $parametersWithDate = $parameters;
        $parametersWithDate['date'] = (new DateTime())->format(DateFormatHelper::DEFAULT_FORMAT);

        $I->sendPut(self::URL_API . "/{$this->productInvoiceFile->getId()}", $parametersWithDate);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson($parameters);
    }

    #[Depends('testPutProductInvoiceFile')]
    public function testDeleteProductInvoiceFile(ApiTester $I): void
    {
        $I->sendDelete(self::URL_API . "/{$this->productInvoiceFile->getId()}");
        $I->seeResponseCodeIsSuccessful();
        $I->assertFileIsDeleted(self::DIRECTORY_FILES, $this->productInvoiceFile->getName());
    }

    /**
     * @return array<string, string>
     */
    private function getPameters(string $name): array
    {
        return [
            'name' => $name,
            'totalAmount' => 127.50,
        ];
    }

    private function assertIsAZip(ApiTester $I, string $file): void
    {
        $zip = new ZipArchive();
        $I->assertTrue(TRUE === $zip->open($file));
        $I->assertEquals($zip->count(), 1);
    }
}
