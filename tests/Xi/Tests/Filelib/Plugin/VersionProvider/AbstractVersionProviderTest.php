<?php

namespace Xi\Tests\Filelib\Plugin\VersionProvider;

use Xi\Tests\Filelib\TestCase;
use Xi\Filelib\File\Upload\FileUpload;
use Xi\Filelib\File\File;
use Xi\Filelib\Storage\Storage;
use Xi\Filelib\Publisher\Publisher;
use Xi\Filelib\File\FileOperator;
use Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider;
use Xi\Filelib\Event\FileEvent;

class AbstractVersionProviderTest extends TestCase
{

    /**
     *
     * @var FileLibrary
     */
    protected $filelib;

    /**
     *
     * @var FileOperator
     */
    protected $fileOperator;

    /**
     *
     * @var Storage
     */
    protected $storage;

    /**
     *
     * @var AbstractVersionProvider
     */
    protected $plugin;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     *
     * @return FileLibrary
     */
    public function setUp()
    {
        $fileOperator = $this->getMockForAbstractClass('Xi\Filelib\File\FileOperator');

        $fileOperator->expects($this->any())->method('getType')
        ->will($this->returnCallback(function(File $file) {
            $split = explode('/', $file->getMimetype());
            return $split[0];
        }));

        $filelib = $this->getMock('Xi\Filelib\FileLibrary');
        $filelib->expects($this->any())->method('getFileOperator')->will($this->returnValue($fileOperator));

        $storage = $this->getMockForAbstractClass('Xi\Filelib\Storage\Storage');
        $filelib->expects($this->any())->method('getStorage')->will($this->returnValue($storage));

        $plugin = $this->getMockBuilder('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider')
            ->setMethods(array('createVersions', 'deleteVersions', 'getStorage', 'getVersions', 'getExtensionFor'))
            ->getMockForAbstractClass();

        $plugin->expects($this->any())->method('getStorage')->will($this->returnValue($storage));

        $plugin->setFilelib($filelib);

        $publisher = $this->getMockBuilder('Xi\Filelib\Publisher\Publisher')
            ->getMockForAbstractClass();
        $filelib->expects($this->any())->method('getPublisher')->will($this->returnValue($publisher));

        $this->plugin = $plugin;
        $this->filelib = $filelib;
        $this->fileOperator = $fileOperator;
        $this->storage = $storage;
        $this->publisher = $publisher;

    }


    public function tearDown()
    {
        parent::tearDown();
    }



    /**
     * @test
     */
    public function gettersAndSettersShouldWorkAsExpected()
    {
        $providesFor = array('image', 'video');
        $this->assertEquals(array(), $this->plugin->getProvidesFor());
        $this->assertSame($this->plugin, $this->plugin->setProvidesFor($providesFor));
        $this->assertEquals($providesFor, $this->plugin->getProvidesFor());

        $identifier = 'xooxer';
        $this->assertNull($this->plugin->getIdentifier());
        $this->assertSame($this->plugin, $this->plugin->setIdentifier($identifier));
        $this->assertEquals($identifier, $this->plugin->getIdentifier());

    }

    /**
     * @test
     * @expectedException Xi\Filelib\FilelibException
     */
    public function initShouldFailWhenIdentifierIsNotDefined()
    {
        $this->plugin->init();
    }



    /**
     * @test
     */
    public function initShouldPassWhenIdentifierIsDefined()
    {
        $this->plugin->setIdentifier('xooxer');
        $this->plugin->init();
    }



    /**
     * @test
     */
    public function initShouldPassWhenIdentifierAndExtensionAreSetAndProvidesAreSetToPlugin()
    {
        $this->plugin->setIdentifier('xooxer');
        $this->plugin->setProvidesFor(array('image', 'video'));

        $this->plugin->init();

    }


    /**
     * @test
     */
    public function initShouldRegisterToProfilesWhenIdentifierAndExtensionAreSetAndProvidesAndProfilesAreSetToPlugin()
    {
        $this->plugin->expects($this->atLeastOnce())->method('getVersions')
                     ->will($this->returnValue(array('xooxer', 'tooxer')));

        $this->plugin->setIdentifier('xooxer');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $lussi = $this->getMock('Xi\Filelib\File\FileProfile');
        $lussi->expects($this->at(0))->method('addFileVersion')->with($this->equalTo('image'), $this->equalTo('xooxer'), $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider'));
        $lussi->expects($this->at(1))->method('addFileVersion')->with($this->equalTo('image'), $this->equalTo('tooxer'), $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider'));
        $lussi->expects($this->at(2))->method('addFileVersion')->with($this->equalTo('video'), $this->equalTo('xooxer'), $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider'));
        $lussi->expects($this->at(3))->method('addFileVersion')->with($this->equalTo('video'), $this->equalTo('tooxer'), $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider'));

        $tussi = $this->getMock('Xi\Filelib\File\FileProfile');
        $tussi->expects($this->at(0))->method('addFileVersion')->with($this->equalTo('image'), $this->equalTo('xooxer'), $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider'));
        $tussi->expects($this->at(1))->method('addFileVersion')->with($this->equalTo('image'), $this->equalTo('tooxer'), $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider'));
        $tussi->expects($this->at(2))->method('addFileVersion')->with($this->equalTo('video'), $this->equalTo('xooxer'), $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider'));
        $tussi->expects($this->at(3))->method('addFileVersion')->with($this->equalTo('video'), $this->equalTo('tooxer'), $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider'));

        $fileOperator = $this->fileOperator;

        $fileOperator->expects($this->any())->method('getProfile')
        ->with($this->logicalOr(
            $this->equalTo('tussi'), $this->equalTo('lussi')
        ))
        ->will($this->returnCallback(function($name) use ($lussi, $tussi) {

            if ($name === 'lussi') {
                return $lussi;
            }

            if ($name === 'tussi') {
                return $tussi;
            }

        }));


        $this->plugin->setFilelib($this->filelib);
        $this->plugin->init();

    }


    public function provideFilesForProvidesForMatching()
    {
        return array(
            array(true, array('profile' => 'tussi', 'mimetype' => 'image/png')),
            array(false, array('profile' => 'tussi', 'mimetype' => 'document/lus')),
            array(false, array('profile' => 'xtussi', 'mimetype' => 'image/xoo')),
            array(true, array('profile' => 'lussi', 'mimetype' => 'video/vii')),
            array(false, array('profile' => 'lussi', 'mimetype' => 'iimage/xoo')),
        );
    }



    /**
     * @test
     * @dataProvider provideFilesForProvidesForMatching
     */
    public function providesForShouldMatchAgainstFileProfileCorrectly($expected, $file)
    {
        $file = File::create($file);

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $this->plugin->setFilelib($this->filelib);

        $this->assertEquals($expected, $this->plugin->providesFor($file));


    }

    /**
     * @test
     */
    public function afterUploadShouldDoNothingWhenPluginDoesNotProvide()
    {
        $this->plugin->expects($this->never())->method('createVersions');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $this->plugin->setFileLib($this->filelib);


        $file = File::create(array('mimetype' => 'iimage/xoo', 'profile' => 'tussi'));
        $event = new FileEvent($file);

        $this->plugin->afterUpload($event);


    }


    /**
     * @test
     */
    public function afterUploadShouldCreateAndStoreVersionWhenPluginProvides()
    {
        $this->plugin->setIdentifier('xooxer');

        $this->plugin->expects($this->once())->method('createVersions')
             ->with($this->isInstanceOf('Xi\Filelib\File\File'))
             ->will($this->returnValue(array('xooxer' => ROOT_TESTS . '/data/temp/life-is-my-enemy.jpg')));

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $filelib = $this->filelib;

        $this->storage->expects($this->once())->method('storeVersion')
                ->with(
                    $this->isInstanceOf('Xi\Filelib\File\File'),
                    $this->equalTo('xooxer'),
                    ROOT_TESTS . '/data/temp/life-is-my-enemy.jpg'
                );

        $file = File::create(array('mimetype' => 'image/xoo', 'profile' => 'tussi'));
        $event = new FileEvent($file);

        $this->createMockedTemporaryFile();

        $this->plugin->afterUpload($event);

        $this->assertFileNotExists(ROOT_TESTS . '/data/temp/life-is-my-enemy.jpg');
    }


    /**
     * @test
     */
    public function onPublishShouldDoNothingWhenPluginDoesNotProvide()
    {
        $this->publisher->expects($this->never())->method('publishVersion');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'iimage/xoo', 'profile' => 'tussi'));

        $event = new FileEvent($file);
        $this->plugin->onPublish($event);

    }


    /**
     * @test
     */
    public function onPublishShouldPublishWhenPluginProvides()
    {
        $this->publisher->expects($this->once())->method('publishVersion');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));
        $this->plugin->expects($this->atLeastOnce())->method('getVersions')
                     ->will($this->returnValue(array('xooxer')));


        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'image/png', 'profile' => 'tussi'));

        $event = new FileEvent($file);
        $this->plugin->onPublish($event);

    }

    /**
     * @test
     */
    public function onUnpublishShouldDoNothingWhenPluginDoesNotProvide()
    {
        $this->publisher->expects($this->never())->method('unpublishVersion');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'iimage/xoo', 'profile' => 'tussi'));

        $event = new FileEvent($file);
        $this->plugin->onUnpublish($event);

    }


    /**
     * @test
     */
    public function onUnpublishShouldUnpublishWhenPluginProvides()
    {
        $this->publisher->expects($this->once())->method('unpublishVersion')
              ->with($this->isInstanceOf('Xi\Filelib\File\File'),
                     $this->equalTo('xooxer'),
                     $this->isInstanceOf('Xi\Filelib\Plugin\VersionProvider\VersionProvider')
               );

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));
        $this->plugin->expects($this->atLeastOnce())->method('getVersions')
                     ->will($this->returnValue(array('xooxer')));


        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'image/png', 'profile' => 'tussi'));

        $event = new FileEvent($file);
        $this->plugin->onUnpublish($event);
    }



    /**
     * @test
     */
    public function onUnpublishShouldExitEarlyWhenPluginDoesntHaveProfile()
    {
        $this->publisher->expects($this->never())->method('unpublishVersion');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'image/png', 'profile' => 'xooxer'));

        $event = new FileEvent($file);
        $this->plugin->onUnpublish($event);
    }


    /**
     * @test
     */
    public function onPublishShouldExitEarlyWhenPluginDoesntHaveProfile()
    {
        $this->publisher->expects($this->never())->method('publishVersion');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'image/png', 'profile' => 'xooxer'));

        $event = new FileEvent($file);
        $this->plugin->onPublish($event);
    }

    /**
     * @test
     */
    public function afterUploadShouldExitEarlyWhenPluginDoesntHaveProfile()
    {

        $this->plugin->expects($this->never())->method('createVersions');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $filelib = $this->filelib;
        $storage = $this->storage;

        $storage->expects($this->never())->method('storeVersion');

        $this->plugin->setFilelib($filelib);

        $file = File::create(array('mimetype' => 'image/xoo', 'profile' => 'xooxer'));
        $event = new FileEvent($file);

        $this->plugin->afterUpload($event);

    }



    /**
     * @test
     */
    public function onDeleteShouldDoNothingWhenPluginDoesNotProvide()
    {
        $this->storage->expects($this->never())->method('deleteVersions');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'iimage/xoo', 'profile' => 'tussi'));
        $event = new FileEvent($file);

        $this->plugin->onDelete($event);

    }


    /**
     * @test
     */
    public function onDeleteShouldDeleteWhenPluginProvides()
    {
        $this->storage->expects($this->once())->method('deleteVersion')
             ->with(
                     $this->isInstanceOf('Xi\Filelib\File\File'),
                     $this->equalTo('xooxer')
              );


        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));
        $this->plugin->expects($this->atLeastOnce())->method('getVersions')
                     ->will($this->returnValue(array('xooxer')));

        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'image/png', 'profile' => 'tussi'));
        $event = new FileEvent($file);

        $this->plugin->onDelete($event);

    }


    /**
     * @test
     */
    public function onDeleteShouldExitEarlyWhenPluginDoesntHaveProfile()
    {
        $this->storage->expects($this->never())->method('deleteVersions');

        $this->plugin->setProvidesFor(array('image', 'video'));
        $this->plugin->setProfiles(array('tussi', 'lussi'));

        $this->plugin->setFileLib($this->filelib);

        $file = File::create(array('mimetype' => 'image/png', 'profile' => 'xooxer'));
        $event = new FileEvent($file);

        $this->plugin->onDelete($event);

    }



    private function createMockedTemporaryFile()
    {
        $path = ROOT_TESTS . '/data/temp/life-is-my-enemy.jpg';
        copy(ROOT_TESTS . '/data/self-lussing-manatee.jpg', $path);

        $this->assertFileExists($path);
    }



    /**
     * @test
     */
    public function getSubscribedEventsShouldReturnCorrectEvents()
    {
        $events = AbstractVersionProvider::getSubscribedEvents();
        $this->assertArrayHasKey('fileprofile.add', $events);
        $this->assertArrayHasKey('file.afterUpload', $events);
        $this->assertArrayHasKey('file.publish', $events);
        $this->assertArrayHasKey('file.unpublish', $events);
        $this->assertArrayHasKey('file.delete', $events);
    }

    /**
     * @test
     */
    public function deleteVersionShouldDelegateToStorage()
    {

        $file = File::create(array('id' => 666));

        $this->storage->expects($this->once())->method('deleteVersion')
                ->with($this->isInstanceOf('Xi\Filelib\File\File'), $this->equalTo('xooxersson'));

        $plugin = $this->getMockBuilder('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider')
            ->setMethods(array('createVersions', 'getStorage', 'getVersions', 'getExtensionFor'))
            ->getMock();

        $plugin->expects($this->once())->method('getStorage')
               ->will($this->returnValue($this->storage));

        $plugin->setIdentifier('xooxersson');
        $plugin->expects($this->atLeastOnce())->method('getVersions')
               ->will($this->returnValue(array('xooxersson')));

        $plugin->deleteVersions($file);

    }


    /**
     * @test
     */
    public function getStorageShouldDelegateToFilelib()
    {
        $plugin = $this->getMockBuilder('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider')
            ->setMethods(array('createVersions', 'getVersions'))
            ->getMockForAbstractClass();
        $plugin->setFilelib($this->filelib);

        $this->filelib->expects($this->once())->method('getStorage');

        $storage = $plugin->getStorage();

    }


    /**
     * @test
     */
    public function getPublisherShouldDelegateToFilelib()
    {
        $plugin = $this->getMockBuilder('Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider')
            ->setMethods(array('createVersions', 'getVersions'))
            ->getMockForAbstractClass();
        $plugin->setFilelib($this->filelib);

        $this->filelib->expects($this->once())->method('getPublisher');

        $storage = $plugin->getPublisher();

    }



}
