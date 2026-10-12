<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Build an in-memory `.zip` chart package from a map of entry name to contents.
 *
 * @param  array<string, string>  $files
 */
function chartPackageZip(array $files): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'chart').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($files as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();

    return new UploadedFile($path, 'chart.zip', 'application/zip', null, true);
}

/**
 * Generate a small JPEG image as raw bytes.
 */
function fakeJpegBytes(int $size = 16): string
{
    $image = imagecreatetruecolor($size, $size);
    imagefill($image, 0, 0, imagecolorallocate($image, 120, 40, 200));

    ob_start();
    imagejpeg($image);
    $bytes = (string) ob_get_clean();

    imagedestroy($image);

    return $bytes;
}

/**
 * A minimal valid maidata file with two difficulties.
 */
function sampleMaidata(): string
{
    return implode("\n", [
        '&title=Test Song',
        '&artist=Test Artist',
        '&wholebpm=180',
        '&first=0.24',
        '&des=Test Charter',
        '&tags=tag1, tag2',
        '&inote_4=(180){4}1,2,3,4,',
        '&lv_4=12',
        '&inote_5=(180){4}1,2,3,4,5,',
        '&lv_5=14+',
        '&des_5=Master Charter',
    ]);
}
