<?php
declare(strict_types=1);

namespace GardenLawn\LogViewer\Block\Adminhtml\Log;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\View\Element\Template;

class View extends Template
{
    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var File
     */
    private File $fileDriver;

    /**
     * @var FormKey
     */
    private FormKey $formKey;

    /**
     * @param Template\Context $context
     * @param DirectoryList $directoryList
     * @param File $fileDriver
     * @param FormKey $formKey
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        DirectoryList    $directoryList,
        File             $fileDriver,
        FormKey          $formKey,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->formKey = $formKey;
    }

    /**
     * @throws FileSystemException
     *
     * @return array
     */
    public function getLogFiles(): array
    {
        $logDirectory = $this->directoryList->getPath(DirectoryList::LOG);
        $logFiles = [];
        $this->getAllLogFiles($logDirectory, $logFiles);

        return $logFiles;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getHumanName(string $path): string
    {
        $parentDir = basename(dirname($path));
        $basename = basename($path);
        $result = $parentDir . DIRECTORY_SEPARATOR . $basename;

        return str_replace('log/', '', $result);
    }

    /**
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('logviewer/log/download');
    }

    /**
     * @return string
     */
    public function getClearUrl(): string
    {
        return $this->getUrl('logviewer/log/clear');
    }

    /**
     * Get current form key
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @param string $directory
     * @param array $logFiles
     *
     * @throws FileSystemException
     *
     * @return void
     */
    private function getAllLogFiles(string $directory, array &$logFiles): void
    {
        $files = $this->fileDriver->readDirectory($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if ($this->fileDriver->isDirectory($file)) {
                $this->getAllLogFiles($file, $logFiles);
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                $logFiles[] = $file;
            }
        }
    }
}
