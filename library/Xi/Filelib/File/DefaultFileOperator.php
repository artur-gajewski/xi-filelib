<?php

namespace Xi\Filelib\File;

use Xi\Filelib\FileLibrary;
use Xi\Filelib\File\FileOperator;
use Xi\Filelib\Folder\FolderOperator;
use Xi\Filelib\AbstractOperator;
use Xi\Filelib\FilelibException;
use Xi\Filelib\Plugin\Plugin;
use InvalidArgumentException;
use Xi\Filelib\File\File;
use Xi\Filelib\Folder\Folder;
use Xi\Filelib\Acl\Acl;
use Xi\Filelib\File\Upload\FileUpload;
use Xi\Filelib\Plugin\VersionProvider\VersionProvider;
use Xi\Filelib\Event\FileProfileEvent;
use Xi\Filelib\Event\FileUploadEvent;
use Xi\Filelib\Event\FileEvent;
use Xi\Filelib\Queue\Queue;

use Xi\Filelib\Command;
use Xi\Filelib\File\Command\FileCommand;
use Xi\Filelib\File\Command\UploadFileCommand;
use Xi\Filelib\File\Command\AfterUploadFileCommand;
use Xi\Filelib\File\Command\UpdateFileCommand;
use Xi\Filelib\File\Command\DeleteFileCommand;
use Xi\Filelib\File\Command\PublishFileCommand;
use Xi\Filelib\File\Command\UnpublishFileCommand;


/**
 * File operator
 * 
 * @author pekkis
 *
 */
class DefaultFileOperator extends AbstractOperator implements FileOperator
{

    const COMMAND_UPLOAD = 'upload';
    const COMMAND_AFTERUPLOAD = 'after_upload';
    const COMMAND_UPDATE = 'update';
    const COMMAND_DELETE = 'delete';
    const COMMAND_PUBLISH = 'publish';
    const COMMAND_UNPUBLISH = 'publish';

    protected $commandStrategies = array(
        self::COMMAND_UPLOAD => Command::STRATEGY_SYNCHRONOUS,
        self::COMMAND_AFTERUPLOAD => Command::STRATEGY_SYNCHRONOUS,
        self::COMMAND_UPDATE => Command::STRATEGY_SYNCHRONOUS,
        self::COMMAND_DELETE => Command::STRATEGY_SYNCHRONOUS,
        self::COMMAND_PUBLISH => Command::STRATEGY_SYNCHRONOUS,
        self::COMMAND_UNPUBLISH => Command::STRATEGY_SYNCHRONOUS,
    );
        
    
    /**
     * @var array Profiles
     */
    private $profiles = array();

    /**
     * @var string Fileitem class
     */
    private $className = 'Xi\Filelib\File\FileItem';
    
    /**
     * Sets fileitem class
     *
     * @param string $className Class name
     * @return DefaultFileOperator
     */
    public function setClass($className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * Returns fileitem class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->className;
    }

    /**
     * Returns an instance of the currently set fileitem class
     * 
     * @param mixed $data Data as array or a file instance
     */
    public function getInstance($data = array())
    {
        $className = $this->getClass();
        $file = new $className();
        if ($data) {
            $file->fromArray($data);
        }
        return $file;
    }

    /**
     * Adds a file profile
     * 
     * @param FileProfile $profile
     * @return FileLibrary
     * @throws InvalidArgumentException
     */
    public function addProfile(FileProfile $profile)
    {
        $identifier = $profile->getIdentifier();
        if (isset($this->profiles[$identifier])) {
            throw new InvalidArgumentException("Profile '{$identifier}' already exists");
        }
        $this->profiles[$identifier] = $profile;
        $profile->setFilelib($this->getFilelib());
        $profile->getLinker()->setFilelib($this->getFilelib());
        
        $this->getEventDispatcher()->addSubscriber($profile);

        $event = new FileProfileEvent($profile);
        $this->getEventDispatcher()->dispatch('fileprofile.add', $event);
        
        return $this;
    }

    /**
     * Returns a file profile
     * 
     * @param string $identifier File profile identifier
     * @throws InvalidArgumentException
     * @return FileProfile
     */
    public function getProfile($identifier)
    {
        if (!isset($this->profiles[$identifier])) {
            throw new InvalidArgumentException("File profile '{$identifier}' not found");
        }

        return $this->profiles[$identifier];
    }

    /**
     * Returns all file profiles
     * 
     * @return array Array of file profiles
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * Updates a file
     *
     * @param File $file
     * @return DefaultFileOperator
     */
    public function update(File $file)
    {
        return $this->executeOrQueue(
            $this->createCommand('Xi\Filelib\File\Command\UpdateFileCommand', array($this, $file)),
            DefaultFileOperator::COMMAND_UPDATE
        );
    }

    /**
     * Finds a file
     *
     * @param mixed $id File id
     * @return \Xi\Filelib\File\File
     */
    public function find($id)
    {
        $file = $this->getBackend()->findFile($id);

        if (!$file) {
            return false;
        }

        $file = $this->getInstance($file);
        return $file;
    }

    public function findByFilename(\Xi\Filelib\Folder\Folder $folder, $filename)
    {
        $file = $this->getBackend()->findFileByFilename($folder, $filename);

        if (!$file) {
            return false;
        }

        $file = $this->getInstance($file);

        return $file;
    }

    /**
     * Finds and returns all files
     *
     * @return \ArrayIterator
     */
    public function findAll()
    {
        $ritems = $this->getBackend()->findAllFiles();

        $items = array();
        foreach ($ritems as $ritem) {
            $item = $this->getInstance($ritem);
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Gets a new upload
     *
     * @param string $path Path to upload file
     * @return FileUpload
     */
    public function prepareUpload($path)
    {
        $upload = new FileUpload($path);
        return $upload;
    }

    /**
     * Uploads file to filelib.
     *
     * @param mixed $upload Uploadable, path or object
     * @param Folder $folder
     * @return File
     * @throws FilelibException
     * @todo Remove the upload kludgeration with prepareUpload
     */
    public function upload($upload, Folder $folder, $profile = 'default')
    {
        return $this->executeOrQueue(
            $this->createCommand('Xi\Filelib\File\Command\UploadFileCommand', array($this, $upload, $folder, $profile)),
            DefaultFileOperator::COMMAND_UPLOAD,
            array(
                Command::STRATEGY_SYNCHRONOUS => function(DefaultFileOperator $op, AfterUploadFileCommand $command) {
                    return $op->executeOrQueue($command, DefaultFileOperator::COMMAND_AFTERUPLOAD);
                }
            )
        );
    }

    /**
     * Deletes a file
     *
     * @param File $file
     */
    public function delete(File $file)
    {
        return $this->executeOrQueue(
            $this->createCommand('Xi\Filelib\File\Command\DeleteFileCommand', array($this, $file)),
            DefaultFileOperator::COMMAND_DELETE
        );
    }

    /**
     * Returns file type of a file
     *
     * @param File File $file item
     * @return string File type
     */
    public function getType(File $file)
    {
        // @todo Semi-mock until mimetype database is pooped in.
        $split = explode('/', $file->getMimetype());
        return $split[0];
    }

    /**
     * Returns whether a file has a certain version
     *
     * @param File $file File item
     * @param string $version Version
     * @return boolean
     */
    public function hasVersion(File $file, $version)
    {
        $profile = $this->getProfile($file->getProfile());
        return $profile->fileHasVersion($file, $version);
    }

    /**
     * Returns version provider for a file/version
     *
     * @param File $file File item
     * @param string $version Version
     * @return VersionProvider Provider
     */
    public function getVersionProvider(File $file, $version)
    {
        $profile = $this->getProfile($file->getProfile());
        return $profile->getVersionProvider($file, $version);
    }

    public function publish(File $file)
    {
        return $this->executeOrQueue(
            $this->createCommand('Xi\Filelib\File\Command\PublishFileCommand', array($this, $file)),
            DefaultFileOperator::COMMAND_PUBLISH
        );
    }

    public function unpublish(File $file)
    {
        return $this->executeOrQueue(
            $this->createCommand('Xi\Filelib\File\Command\UnpublishFileCommand', array($this, $file)),
            DefaultFileOperator::COMMAND_UNPUBLISH
        );
       
    }

    public function addPlugin(Plugin $plugin, $priority = 0)
    {
        foreach ($plugin->getProfiles() as $profileIdentifier) {
            $profile = $this->getProfile($profileIdentifier);
            $profile->addPlugin($plugin, $priority);
        }
    }
    

    /**
     *
     * @return FolderOperator
     */
    public function getFolderOperator()
    {
        return $this->getFilelib()->getFolderOperator();
    }
    
}