<?php

namespace ipl\Tests\Html\FormElement;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use ipl\Html\Form;
use ipl\Html\FormElement\FileElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\Lib\FileElementWithAdjustableConfig;
use ipl\Tests\Html\TestCase;
use Psr\Http\Message\StreamInterface;

class FileElementTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testElementLoading(): void
    {
        $form = (new Form())
            ->addElement('file', 'test_file');

        $this->assertInstanceOf(FileElement::class, $form->getElement('test_file'));
    }

    public function testRendering()
    {
        $file = new FileElement('test_file', ['accept' => ['image/png', 'image/jpeg']]);

        $this->assertHtml('<input name="test_file" type="file" accept="image/png, image/jpeg">', $file);
    }

    public function testSetValueAcceptsEmptyValues()
    {
        $file = new FileElement('test_file');
        $file->setValue(null);

        $this->assertNull($file->getValue());

        $file->setValue([]);

        $this->assertNull($file->getValue());
    }

    public function testValuePopulationOnlyWorksWithUploadedFiles()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string is not an uploaded file');

        $file = new FileElement('test_file');
        $file->setValue('lorem ipsum dolorem');
    }

    public function testUploadedFiles()
    {
        $fileToUpload = new UploadedFile(
            'test/test.pdf',
            500,
            0,
            'test.pdf',
            'application/pdf'
        );

        $req = (new ServerRequest('POST', ServerRequest::getUriFromGlobals()))
            ->withUploadedFiles(['test_file' => $fileToUpload])
            ->withParsedBody([]);

        $form = (new Form())
            ->addElement('file', 'test_file')
            ->handleRequest($req);

        $this->assertSame($form->getValue('test_file'), $fileToUpload);
    }

    public function testUploadedFileIsPreservedAcrossRequests()
    {
        $createForm = function () {
            return new class extends Form {
                protected function assemble()
                {
                    $this->addElement('file', 'test', [
                        'destination' => sys_get_temp_dir()
                    ]);
                }
            };
        };

        // User submits a form after choosing a file

        $firstFile = new UploadedFile(
            Utils::streamFor('lorem ipsum dolorem'),
            19,
            0,
            'test.txt',
            'text/plain'
        );

        $firstRequest = (new ServerRequest('POST', ServerRequest::getUriFromGlobals()))
            ->withUploadedFiles(['test' => $firstFile])
            ->withParsedBody([]);

        $firstForm = $createForm();
        $firstForm->handleRequest($firstRequest);

        $this->assertSame(
            'test.txt',
            $firstForm->getElement('test')->getValue()->getClientFilename()
        );

        // User interacts with an autosubmit element and did not choose the file again

        // In this case, the browser sends an empty form part
        $secondFile = new UploadedFile(null, 0, UPLOAD_ERR_NO_FILE);

        $secondRequest = (new ServerRequest('POST', ServerRequest::getUriFromGlobals()))
            // But since the file element injects a hidden input, the file name is preserved
            ->withUploadedFiles(['test' => $secondFile, 'chosen_file_test' => 'test.txt'])
            ->withParsedBody([]);

        $secondForm = $createForm();
        $secondForm->handleRequest($secondRequest);

        $this->assertSame(
            'lorem ipsum dolorem',
            $secondForm->getElement('test')->getValue()->getStream()->getContents()
        );

        // The user may also remove a file after choosing it

        $thirdRequest = (new ServerRequest('POST', ServerRequest::getUriFromGlobals()))
            ->withUploadedFiles(['chosen_file_test' => 'test.txt', 'remove_file_test' => 'test.txt'])
            ->withParsedBody([]);

        $thirdForm = $createForm();
        $thirdForm->handleRequest($thirdRequest);

        $this->assertNull($thirdForm->getValue('test'));
    }

    public function testUploadedFileCanBeMoved()
    {
        $form = new class extends Form {
            protected function assemble()
            {
                $this->addElement('file', 'test');
            }
        };

        $firstFile = new UploadedFile(
            Utils::streamFor('lorem ipsum dolorem'),
            19,
            0,
            'test.txt',
            'text/plain'
        );

        $firstRequest = (new ServerRequest('POST', ServerRequest::getUriFromGlobals()))
            ->withUploadedFiles(['test' => $firstFile])
            ->withParsedBody([]);

        $form->handleRequest($firstRequest);

        $filePath = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'test.txt']);

        $form->getValue('test')->moveTo($filePath);

        $this->assertFileExists($filePath);
    }

    public function testUploadedFileCanBeStreamed()
    {
        $form = new class extends Form {
            protected function assemble()
            {
                $this->addElement('file', 'test');
            }
        };

        $firstFile = new UploadedFile(
            Utils::streamFor('lorem ipsum dolorem'),
            19,
            0,
            'test.txt',
            'text/plain'
        );

        $firstRequest = (new ServerRequest('POST', ServerRequest::getUriFromGlobals()))
            ->withUploadedFiles(['test' => $firstFile])
            ->withParsedBody([]);

        $form->handleRequest($firstRequest);

        $stream = $form->getValue('test')->getStream();

        $this->assertInstanceOf(StreamInterface::class, $stream);

        $this->assertSame(
            'lorem ipsum dolorem',
            $stream->getContents()
        );
    }

    public function testPreservedFileCanBeMoved()
    {
        $form = new class extends Form {
            protected function assemble()
            {
                $this->addElement('file', 'test', [
                    'destination' => sys_get_temp_dir()
                ]);
            }
        };

        $firstFile = new UploadedFile(
            Utils::streamFor('lorem ipsum dolorem'),
            19,
            0,
            'test.txt',
            'text/plain'
        );

        $firstRequest = (new ServerRequest('POST', ServerRequest::getUriFromGlobals()))
            ->withUploadedFiles(['test' => $firstFile])
            ->withParsedBody([]);

        $form->handleRequest($firstRequest);

        $filePath = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'test.txt']);

        $form->getValue('test')->moveTo($filePath);

        $this->assertFileExists($filePath);
    }

    public function testPreservedFileCanBeStreamed()
    {
        $form = new class extends Form {
            protected function assemble()
            {
                $this->addElement('file', 'test', [
                    'destination' => sys_get_temp_dir()
                ]);
            }
        };

        $firstFile = new UploadedFile(
            Utils::streamFor('lorem ipsum dolorem'),
            19,
            0,
            'test.txt',
            'text/plain'
        );

        $firstRequest = (new ServerRequest('POST', ServerRequest::getUriFromGlobals()))
            ->withUploadedFiles(['test' => $firstFile])
            ->withParsedBody([]);

        $form->handleRequest($firstRequest);

        $stream = $form->getValue('test')->getStream();

        $this->assertInstanceOf(StreamInterface::class, $stream);

        $this->assertSame(
            'lorem ipsum dolorem',
            $stream->getContents()
        );
    }

    public function testMutipleAttributeAlsoChangesNameAttribute()
    {
        $file = new FileElement('test_file', ['multiple' => true]);

        $this->assertHtml('<input multiple name="test_file[]" type="file">', $file);
        $this->assertSame($file->getName(), 'test_file');
    }

    public function testDefaultMaxFileSizeAsBytesIsParsedCorrectly()
    {
        $element = new FileElementWithAdjustableConfig('test');
        $element->setValue(new UploadedFile('test', 500, 0));

        $element::$defaultMaxFileSize = null;
        $element::$uploadMaxFilesize = '500';
        $element::$postMaxSize = '1000'; // Just needs to be bigger than 500

        $this->assertTrue($element->isValid(), implode("\n", $element->getMessages()));
    }

    public function testDefaultMaxFileSizeAsKiloBytesIsParsedCorrectly()
    {
        $element = new FileElementWithAdjustableConfig('test');
        $element->setValue(new UploadedFile('test', 1024, 0));

        $element::$defaultMaxFileSize = null;
        $element::$uploadMaxFilesize = '1K';
        $element::$postMaxSize = '2K'; // Just needs to be bigger than 1K

        $this->assertTrue($element->isValid(), implode("\n", $element->getMessages()));
    }

    public function testDefaultMaxFileSizeAsMegaBytesIsParsedCorrectly()
    {
        $element = new FileElementWithAdjustableConfig('test');
        $element->setValue(new UploadedFile('test', 102400, 0));

        $element::$defaultMaxFileSize = null;
        $element::$uploadMaxFilesize = '1M';
        $element::$postMaxSize = '2M'; // Just needs to be bigger than 1M

        $this->assertTrue($element->isValid(), implode("\n", $element->getMessages()));
    }

    public function testDefaultMaxFileSizeAsGigaBytesIsParsedCorrectly()
    {
        $element = new FileElementWithAdjustableConfig('test');
        $element->setValue(new UploadedFile('test', 1073741824, 0));

        $element::$defaultMaxFileSize = null;
        $element::$uploadMaxFilesize = '1G';
        $element::$postMaxSize = '2G'; // Just needs to be bigger than 1G

        $this->assertTrue($element->isValid(), implode("\n", $element->getMessages()));
    }

    /**
     * @depends testDefaultMaxFileSizeAsKiloBytesIsParsedCorrectly
     */
    public function testUploadMaxFilesizeOverrulesPostMaxSize()
    {
        $element = new FileElementWithAdjustableConfig('test');
        $element->setValue(new UploadedFile('test', 1024, 0));

        $element::$defaultMaxFileSize = null;
        $element::$uploadMaxFilesize = '1K';
        $element::$postMaxSize = '2K'; // Just needs to be bigger than 1K

        $this->assertTrue($element->isValid(), implode("\n", $element->getMessages()));

        // ...if possible

        $element::$defaultMaxFileSize = null;
        $element::$uploadMaxFilesize = '2K';
        $element::$postMaxSize = '1K';

        $this->assertTrue($element->isValid(), implode("\n", $element->getMessages()));

        $element->setValue(new UploadedFile('test', 2048, 0));
        $element::$defaultMaxFileSize = null;

        $this->assertFalse($element->isValid());
    }
}
