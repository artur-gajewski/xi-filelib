<?php

namespace Xi\Filelib\Publisher;

use Xi\Filelib\FileLibrary;
use Xi\Filelib\File\File;
use Xi\Filelib\Plugin\VersionProvider\VersionProvider;


/**
 * Publisher interface
 *
 * @author pekkis
 *
 */
interface Publisher
{

    /**
     * Publishes a file
     *
     * @param File $file
     */
    public function publish(File $file);

    /**
     * Publishes a version of a file
     *
     * @param File $file
     * @param string $version
     * @param VersionProvider $versionProvider
     */
    public function publishVersion(File $file, $version, VersionProvider $versionProvider);

    /**
     * Unpublishes a file
     *
     * @param File $file
     */
    public function unpublish(File $file);

    /**
     * Unpublishes a version of a file
     *
     * @param File $file
     * @param string $version
     * @param VersionProvider $versionProvider
     */
    public function unpublishVersion(File $file, $version, VersionProvider $versionProvider);

    /**
     * Returns url to a file
     *
     * @param File $file
     * @return string
     */
    public function getUrl(File $file);

    /**
     * Returns url to a version of a file
     *
     * @param File $file
     * @param string $version
     * @param VersionProvider $version
     * @return string
     */
    public function getUrlVersion(File $file, $version, VersionProvider $versionProvider);


    /**
     * Sets filelib
     * @param FileLibrary
     * @return Publisher
     */
    public function setFilelib(FileLibrary $filelib);


}