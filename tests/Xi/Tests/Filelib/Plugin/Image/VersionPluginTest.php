<?php

namespace Xi\Tests\Filelib\Plugin\Image;

use Imagick;
use Xi\Filelib\Plugin\Image\VersionPlugin;
use Xi\Filelib\File\File;

class VersionPluginTest extends TestCase
{
    /**
     * @test
     */
    public function classShouldExist()
    {
        $this->assertTrue(class_exists('Xi\Filelib\Plugin\Image\VersionPlugin'));
        $this->assertArrayHasKey('Xi\Filelib\Plugin\AbstractPlugin', class_parents('Xi\Filelib\Plugin\Image\VersionPlugin'));
    }

    /**
     * @test
     */
    public function pluginShouldProvideForImage()
    {
        $plugin = new VersionPlugin();
        $this->assertEquals(array('image'), $plugin->getProvidesFor());
    }

    /**
     * @test
     */
    public function getImageMagickHelperShouldReturnImageMagickHelper()
    {
        $plugin = new VersionPlugin();
        $helper = $plugin->getImageMagickHelper();

        $this->assertInstanceOf('Xi\Filelib\Plugin\Image\ImageMagickHelper', $helper);

        $this->assertSame($helper, $plugin->getImageMagickHelper());
    }

    /**
     * @test
     */
    public function createVersionsShouldCreateVersions()
    {
        $retrievedPath = ROOT_TESTS . '/data/illusive-manatee.jpg';

        $file = File::create(array('id' => 1));

        $fobject = $this->getMockBuilder('Xi\Filelib\File\FileObject')
                        ->setConstructorArgs(array(ROOT_TESTS . '/data/self-lussing-manatee.jpg'))
                        ->getMock();
        $fobject->expects($this->once())->method('getPathName')->will($this->returnValue($retrievedPath));


        $storage = $this->getMockForAbstractClass('Xi\Filelib\Storage\Storage');
        $storage->expects($this->once())->method('retrieve')->with($this->equalTo($file))->will($this->returnValue($fobject));

        $filelib = $this->getMock('Xi\Filelib\FileLibrary');

        $helper = $this->getMock('Xi\Filelib\Plugin\Image\ImageMagickHelper');

        $mock = $this->getMock('Imagick');
        $mock->expects($this->once())->method('writeImage')->with($this->matchesRegularExpression("#^/tmp/dir#"));

        $helper->expects($this->once())->method('createImagick')->with($this->equalTo($retrievedPath))->will($this->returnValue($mock));
        $helper->expects($this->once())->method('execute')->with($this->equalTo($mock));

        $filelib->expects($this->any())->method('getTempDir')->will($this->returnValue('/tmp/dir'));
        $filelib->expects($this->any())->method('getStorage')->will($this->returnValue($storage));

        $plugin = $this->getMockBuilder('Xi\Filelib\Plugin\Image\VersionPlugin')
                       ->setMethods(array('getImageMagickHelper'))
                        ->disableOriginalConstructor()
                       ->getMock();

        $plugin->expects($this->any())->method('getImageMagickHelper')->will($this->returnValue($helper));

        $plugin->setFilelib($filelib);
        $ret = $plugin->createVersions($file);

        $this->assertInternalType('array', $ret);

        foreach ($ret as $version => $tmp) {
            $this->assertRegExp("#/tmp/dir#", $tmp);
        }





    }


    /**
     * @test
     */
    public function getSubscribedEventsShouldReturnCorrectEvents()
    {
        $events = VersionPlugin::getSubscribedEvents();
        $this->assertArrayHasKey('fileprofile.add', $events);
        $this->assertArrayHasKey('file.afterUpload', $events);
        $this->assertArrayHasKey('file.publish', $events);
        $this->assertArrayHasKey('file.unpublish', $events);
        $this->assertArrayHasKey('file.delete', $events);
    }

    /**
     * @test
     */
    public function getVersionsShouldReturnArrayOfOneContainingIdentifier()
    {
         $plugin = new VersionPlugin();
         $plugin->setIdentifier('xooxer');

         $this->assertEquals(array('xooxer'), $plugin->getVersions());
    }

    /**
     * @test
     */
    public function gettersAndSettersShouldWork()
    {
        $plugin = new VersionPlugin();

        $extension = 'lus';
        $this->assertNull($plugin->getExtension());
        $this->assertSame($plugin, $plugin->setExtension($extension));
        $this->assertEquals($extension, $plugin->getExtension());

    }

    /**
     * @test
     */
    public function getExtensionForShouldDelegateToGetExtension()
    {
        $plugin = $this->getMockBuilder('Xi\Filelib\Plugin\Image\VersionPlugin')
                        ->setMethods(array('getExtension'))
                        ->getMock();

        $plugin->expects($this->once())->method('getExtension');

        $plugin->getExtensionFor('xooxoo');

    }


}