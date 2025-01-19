<?php

namespace Test\Psr7;

use ByJG\WebRequest\Psr7\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    public function testCreateUploadedFile()
    {
        $uploadedFile = new UploadedFile('content', strlen('content'), UPLOAD_ERR_OK, 'test.txt', 'text/plain');

        $this->assertEquals('content', $uploadedFile->getStream()->getContents());
        $this->assertEquals(strlen('content'), $uploadedFile->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->getError());
        $this->assertEquals('test.txt', $uploadedFile->getClientFilename());
        $this->assertEquals('text/plain', $uploadedFile->getClientMediaType());
    }

    public function testMoveTo()
    {
        $uploadedFile = new UploadedFile('content', strlen('content'), UPLOAD_ERR_OK, 'test.txt', 'text/plain');
        $targetPath = '/tmp/test.txt';
        $uploadedFile->moveTo($targetPath);

        try {
            $this->assertFileExists($targetPath);
            $this->assertEquals('content', file_get_contents($targetPath));
            unlink($targetPath);
        } finally {
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
        }
    }

    public function testParseGlobalFiles()
    {
        $_FILES = [
            'file' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/php1h4j1o',
                'error' => UPLOAD_ERR_OK,
                'size' => strlen('content'),
            ],
            'file2' => [
                'name' => 'test2.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php2s8245a',
                'error' => UPLOAD_ERR_CANT_WRITE,
                'size' => 0,
            ]
        ];
        file_put_contents($_FILES['file']['tmp_name'], 'content');

        try {
            /** @var UploadedFile[] $uploadedFiles */
            $uploadedFiles = UploadedFile::parseFilesGlobal();

            $this->assertCount(2, $uploadedFiles);
            $this->assertEquals('test.txt', $uploadedFiles['file']->getClientFilename());
            $this->assertEquals('text/plain', $uploadedFiles['file']->getClientMediaType());
            $this->assertEquals(UPLOAD_ERR_OK, $uploadedFiles['file']->getError());
            $this->assertEquals('content', $uploadedFiles['file']->getStream()->getContents());
            $this->assertEquals(strlen('content'), $uploadedFiles['file']->getSize());

            $this->assertEquals('test2.jpg', $uploadedFiles['file2']->getClientFilename());
            $this->assertEquals('image/jpeg', $uploadedFiles['file2']->getClientMediaType());
            $this->assertEquals(UPLOAD_ERR_CANT_WRITE, $uploadedFiles['file2']->getError());
            $this->assertEquals('', $uploadedFiles['file2']->getStream()->getContents());
            $this->assertEquals(0, $uploadedFiles['file2']->getSize());
        } finally {
            foreach ($_FILES as $file) {
                if (file_exists($file['tmp_name'])) {
                    unlink($file['tmp_name']);
                }
            }
        }
    }

    /**
     * The $_FILES superglobal has some well-known problems when dealing with arrays of file inputs.
     * As an example, if you have a form that submits an array of files — e.g.,
     * the input name "files", submitting files[0] and files[1] — PHP will represent differently
     *
     * @return void
     */
    public function testFilesGlobalCase1()
    {
        $_FILES = [
            'files' => [
                'name' => [
                    0 => 'file0.txt',
                    1 => 'file1.html',
                ],
                'type' => [
                    0 => 'text/plain',
                    1 => 'text/html',
                ],
                'tmp_name' => [
                    0 => '/tmp/php1h4j1o',
                    1 => '/tmp/php2s8245a',
                ],
                'error' => [
                    0 => UPLOAD_ERR_OK,
                    1 => UPLOAD_ERR_CANT_WRITE,
                ],
                'size' => [
                    0 => strlen('content0'),
                    1 => 0,
                ],
            ],
        ];
        file_put_contents($_FILES['files']['tmp_name'][0], 'content0');

        try {
            /** @var UploadedFile[][] $uploadedFiles */
            $uploadedFiles = UploadedFile::parseFilesGlobal();

            $this->assertCount(1, $uploadedFiles);
            $this->assertCount(2, $uploadedFiles['files']);
            $this->assertEquals('file0.txt', $uploadedFiles['files'][0]->getClientFilename());
            $this->assertEquals('text/plain', $uploadedFiles['files'][0]->getClientMediaType());
            $this->assertEquals(UPLOAD_ERR_OK, $uploadedFiles['files'][0]->getError());
            $this->assertEquals('content0', $uploadedFiles['files'][0]->getStream()->getContents());
            $this->assertEquals(strlen('content0'), $uploadedFiles['files'][0]->getSize());

            $this->assertEquals('file1.html', $uploadedFiles['files'][1]->getClientFilename());
            $this->assertEquals('text/html', $uploadedFiles['files'][1]->getClientMediaType());
            $this->assertEquals(UPLOAD_ERR_CANT_WRITE, $uploadedFiles['files'][1]->getError());
            $this->assertEquals('', $uploadedFiles['files'][1]->getStream()->getContents());
            $this->assertEquals(0, $uploadedFiles['files'][1]->getSize());
        } finally {
            foreach ($_FILES['files']['tmp_name'] as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * In the case of an input using array notation for the name:
     *
     * <input type="file" name="my-form[details][avatar]" />
     * @return void
     */
    public function testFilesGlobalCase2()
    {
        $files = [
            'my-form' => [
                'name' => [
                    'details' => [
                        'avatar' => 'my-avatar.png',
                    ],
                ],
                'type' => [
                    'details' => [
                        'avatar' => 'image/png',
                    ],
                ],
                'tmp_name' => [
                    'details' => [
                        'avatar' => 'phpmFLrzD',
                    ],
                ],
                'error' => [
                    'details' => [
                        'avatar' => 0,
                    ],
                ],
                'size' => [
                    'details' => [
                        'avatar' => 86,
                    ],
                ],
            ],
        ];

        $binJpg = hex2bin('FFD8FFE000104A46494600010101006000600000FFDB004300080606070605080707070909080A0A09080A0C140D0C0B0B0C1912130F141D1A1F1E1D1A1C1C20242E2720222C231C1C2837292C303134343D3C394034');
        file_put_contents($files['my-form']['tmp_name']['details']['avatar'], $binJpg);

        try {
            /** @var UploadedFile[][] $uploadedFiles */
            $uploadedFiles = UploadedFile::parseFilesGlobal($files);

            $this->assertCount(1, $uploadedFiles);
            $this->assertCount(1, $uploadedFiles['my-form']);

            $this->assertEquals('my-avatar.png', $uploadedFiles['my-form']['details']['avatar']->getClientFilename());
            $this->assertEquals('image/png', $uploadedFiles['my-form']['details']['avatar']->getClientMediaType());
            $this->assertEquals(0, $uploadedFiles['my-form']['details']['avatar']->getError());
            $this->assertEquals($binJpg, $uploadedFiles['my-form']['details']['avatar']->getStream()->getContents());
            $this->assertEquals(strlen($binJpg), $uploadedFiles['my-form']['details']['avatar']->getSize());
        } finally {
            foreach ($files['my-form']['tmp_name']['details'] as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * In the case of an input using array notation for the name:
     *
     * <input type="file" name="my-form[details][avatars][]" />
     */
    public function testFilesGlobalCase3()
    {
        $files = [
            'my-form' => [
                'name' => [
                    'details' => [
                        'avatars' => [
                            0 => 'my-avatar0.png',
                            1 => 'my-avatar1.jpg',
                        ],
                    ],
                ],
                'type' => [
                    'details' => [
                        'avatars' => [
                            0 => 'image/png',
                            1 => 'image/jpeg',
                        ],
                    ],
                ],
                'tmp_name' => [
                    'details' => [
                        'avatars' => [
                            0 => '/tmp/phpmFLrzD',
                            1 => '/tmp/phpmFLrzE',
                        ],
                    ],
                ],
                'error' => [
                    'details' => [
                        'avatars' => [
                            0 => 0,
                            1 => 0,
                        ],
                    ],
                ],
                'size' => [
                    'details' => [
                        'avatars' => [
                            0 => 86,
                            1 => 86,
                        ],
                    ],
                ],
            ],
        ];

        $binPng = hex2bin('89504E470D0A1A0A0000000D4948445200000001000000010806');
        $binJpg = hex2bin('FFD8FFE000104A46494600010101006000600000FFDB004300080606070605080707070909080A0A09080A0C140D0C0B0B0C1912130F141D1A1F1E1D1A1C1C20242E2720222C231C1C2837292C303134343D3C394034');
        file_put_contents($files['my-form']['tmp_name']['details']['avatars'][0], $binPng);
        file_put_contents($files['my-form']['tmp_name']['details']['avatars'][1], $binJpg);

        try {
            /** @var UploadedFile[][][][] $uploadedFiles */
            $uploadedFiles = UploadedFile::parseFilesGlobal($files);

            $this->assertCount(1, $uploadedFiles);
            $this->assertCount(1, $uploadedFiles['my-form']);
            $this->assertCount(2, $uploadedFiles['my-form']['details']['avatars']);

            $this->assertEquals('my-avatar0.png', $uploadedFiles['my-form']['details']['avatars'][0]->getClientFilename());
            $this->assertEquals('image/png', $uploadedFiles['my-form']['details']['avatars'][0]->getClientMediaType());
            $this->assertEquals(0, $uploadedFiles['my-form']['details']['avatars'][0]->getError());
            $this->assertEquals($binPng, $uploadedFiles['my-form']['details']['avatars'][0]->getStream()->getContents());
            $this->assertEquals(strlen($binJpg), $uploadedFiles['my-form']['details']['avatars'][0]->getSize());

            $this->assertEquals('my-avatar1.jpg', $uploadedFiles['my-form']['details']['avatars'][1]->getClientFilename());
            $this->assertEquals('image/jpeg', $uploadedFiles['my-form']['details']['avatars'][1]->getClientMediaType());
            $this->assertEquals(0, $uploadedFiles['my-form']['details']['avatars'][1]->getError());
            $this->assertEquals($binJpg, $uploadedFiles['my-form']['details']['avatars'][1]->getStream()->getContents());
            $this->assertEquals(strlen($binJpg), $uploadedFiles['my-form']['details']['avatars'][1]->getSize());
        } finally {
            foreach ($files['my-form']['tmp_name']['details']['avatars'] as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
