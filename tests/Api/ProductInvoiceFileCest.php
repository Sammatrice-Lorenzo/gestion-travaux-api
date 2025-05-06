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

final class ProductInvoiceFileCest
{
    private const string FILE_NAME = 'InvoiceTemplate.pdf';

    private User $user;

    private ProductInvoiceFile $productInvoiceFile;

    private DateTime $date;

    private const string URL_API = '/api/product_invoice_files';

    public function _before(ApiTester $I): void
    {
        /** @var User $user */
        $user = $I->grabEntity(User::class, ['email' => 'user@test.com']);
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
        $I->haveHttpHeader('Content-Type', 'mutltipart/form-data');
        $filePath = codecept_data_dir(self::FILE_NAME);

        $client = new \GuzzleHttp\Client(['base_uri' => 'https://localhost:8000', 'verify' => false]);

        $token = $I->loginWithGuzzleHttp($client);

        $client->request('POST', '/api/product_invoice_files', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ],
            'multipart' => [
                [
                    'name' => 'date',
                    'contents' => '2025-05-05',
                ],
                [
                    'name' => 'files[]',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => self::FILE_NAME,
                ],
            ],
        ]);

        $I->seeResponseCodeIsSuccessful();
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
    public function testDeleteWorkByUser(ApiTester $I): void
    {
        $I->sendDelete(self::URL_API . "/{$this->productInvoiceFile->getId()}");
        $I->seeResponseCodeIsSuccessful();
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
