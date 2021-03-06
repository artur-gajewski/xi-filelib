<?php

namespace Xi\Filelib\Folder\Command;

use Xi\Filelib\Folder\FolderOperator;
use Xi\Filelib\File\FileOperator;
use Xi\Filelib\Folder\Folder;
use Serializable;

class DeleteFolderCommand extends AbstractFolderCommand implements Serializable
{

    /**
     *
     * @var FileOperator
     */
    private $fileOperator;

    /**
     *
     * @var Folder
     */
    private $folder;


    public function __construct(FolderOperator $folderOperator, FileOperator $fileOperator, Folder $folder)
    {
        parent::__construct($folderOperator);
        $this->fileOperator = $fileOperator;
        $this->folder = $folder;
    }


    public function execute()
    {
        foreach ($this->folderOperator->findSubFolders($this->folder) as $childFolder) {
            $command = $this->folderOperator->createCommand('Xi\Filelib\Folder\Command\DeleteFolderCommand', array(
                $this->folderOperator, $this->fileOperator, $childFolder
            ));
            $command->execute();
        }

        foreach ($this->folderOperator->findFiles($this->folder) as $file) {
            $command = $this->folderOperator->createCommand('Xi\Filelib\File\Command\DeleteFileCommand', array(
                $this->fileOperator, $file
            ));
            $command->execute();
        }

        $this->folderOperator->getBackend()->deleteFolder($this->folder);
    }



    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->folder = $data['folder'];
    }


    public function serialize()
    {
        return serialize(array(
           'folder' => $this->folder,
        ));
    }


}