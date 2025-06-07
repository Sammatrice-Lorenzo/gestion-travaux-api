<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Entity\Work;
use App\Entity\WorkImage;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;
use App\Tests\Enum\UserFixturesEnum;

final class WorkImageCest
{
    private const string IMAGE_NAME = 'fakeImage.jpg';

    private const string DIRECTORY_IMAGES = 'work_images';

    private User $user;

    private Work $work;

    private const string URL_API = '/api/work_images';

    public function _before(ApiTester $I): void
    {
        /** @var User $user */
        $user = $I->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;

        /**
         * @var Work $work
         */
        $this->work = $I->grabEntity(Work::class, ['user' => $this->user]);

        $I->loginAs();
    }

    public function testAddWorkImage(ApiTester $I): void
    {
        $filePath = codecept_data_dir(self::IMAGE_NAME);
        $I->haveHttpHeader('Content-Type', '');
        $response = $I->sendPost(self::URL_API, [
            'workId' => $this->work->getId(),
        ], [
            'images[]' => [
                'name' => self::IMAGE_NAME,
                'type' => 'image',
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($filePath),
                'tmp_name' => $filePath,
            ],
        ]);
        $I->seeResponseCodeIsSuccessful();
        $imageName = $this->getImagePath($I);

        $I->assertFileIsUploaded(self::DIRECTORY_IMAGES, $imageName);
    }

    #[Depends('testAddWorkImage')]
    public function testGetCollectionProductInvoiceFile(ApiTester $I): void
    {
        $I->sendGet(self::URL_API . "?workId={$this->work->getId()}");
        $I->seeResponseCodeIsSuccessful();
        $imageName = explode('-', $this->getImagePath($I))[0];

        $I->assertEquals(strtolower(self::IMAGE_NAME), "{$imageName}.jpg");
    }

    #[Depends('testGetCollectionProductInvoiceFile')]
    public function testDeleteWorkImage(ApiTester $I): void
    {
        /**
         * @var WorkImage $workImage
         */
        $workImage = $I->grabEntity(WorkImage::class, ['work' => $this->work]);
        $I->sendDelete(self::URL_API . "/{$workImage->getId()}");
        $I->seeResponseCodeIsSuccessful();

        $I->assertFileIsDeleted(self::DIRECTORY_IMAGES, $workImage->getImageName());
    }

    private function getImagePath(ApiTester $I): string
    {
        $response = $I->grabResponse();
        $data = json_decode($response, true);

        return $data[0]['imageName'];
    }
}
