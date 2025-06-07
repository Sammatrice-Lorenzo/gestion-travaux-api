<?php

namespace App\Tests\Unit\Entity;

use DateTimeImmutable;
use App\Entity\WorkImage;
use Symfony\Component\HttpFoundation\File\File;

final class WorkImageTest extends AbstractEntityTestDefault
{
    private DateTimeImmutable $date;

    public function _before(): void
    {
        $this->date = new DateTimeImmutable();
    }

    public function testRightEntity(): void
    {
        $workImage = $this->generateValidEntity();

        $this->tester->assertEquals($workImage->getUpdatedAt(), $this->date);
        $this->assertInstanceOf(File::class, $workImage->getImageFile());
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(2, new WorkImage());
    }

    private function generateValidEntity(): WorkImage
    {
        $imagePath = codecept_data_dir('fakeImage.jpg');

        return (new WorkImage())
            ->setImageName('Fake Image')
            ->setUpdatedAt($this->date)
            ->setImageFile(new File($imagePath))
        ;
    }
}
