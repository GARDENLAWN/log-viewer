<?php
declare(strict_types=1);

namespace GardenLawn\LogViewer\Controller\Adminhtml\Log;

use Laminas\Http\Response;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;

class Download extends Action implements HttpPostActionInterface
{
    public const string ADMIN_RESOURCE = 'GardenLawn_LogViewer::download';

    /**
     * @var File
     */
    private File $file;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @param Context $context
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context       $context,
        File          $file,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        try {
            $filePath = $this->getRequest()->getPost('file_path');
            $lines = (int)$this->getRequest()->getPost('lines', 100); // Default to 100 lines

            if (!$filePath) {
                throw new LocalizedException(__('File path is missing.'));
            }

            $logDirectory = $this->directoryList->getPath(DirectoryList::LOG);

            // Validate that the requested file path is within the log directory
            if (strpos($filePath, $logDirectory) !== 0 || !is_file($filePath)) {
                throw new LocalizedException(__('Invalid file path or file does not exist.'));
            }

            $content = $this->tailFile($filePath, $lines);

            return $this->getResponse()
                ->setHttpResponseCode(Response::STATUS_CODE_200)
                ->setHeader('Content-Type', 'application/text', true)
                ->setBody($content);

        } catch (LocalizedException $e) {
            return $this->getResponse()
                ->setHttpResponseCode(Response::STATUS_CODE_400)
                ->setBody($e->getMessage());
        } catch (\Exception $e) {
            return $this->getResponse()
                ->setHttpResponseCode(Response::STATUS_CODE_500)
                ->setBody(__('An error occurred while downloading the file.'));
        }
    }

    /**
     * Reads the last N lines of a file.
     *
     * @param string $filepath
     * @param int $lines
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function tailFile(string $filepath, int $lines): string
    {
        $f = $this->file->fileOpen($filepath, 'r');
        if (!$f) {
            return '';
        }

        $stat = $this->file->stat($filepath);
        $size = $stat['size'];
        if ($size === 0) {
            $this->file->fileClose($f);
            return '';
        }

        $bufferSize = 4096;
        $buffer = '';
        $lineCount = 0;
        $position = $size;

        while ($position > 0) {
            $seekPosition = max(0, $position - $bufferSize);
            $bytesToRead = $position - $seekPosition;

            $this->file->fileSeek($f, $seekPosition);
            $readBuffer = $this->file->fileRead($f, $bytesToRead);
            $buffer = $readBuffer . $buffer;
            $lineCount = substr_count($buffer, "\n");

            if ($lineCount >= $lines) {
                break;
            }

            $position = $seekPosition;
        }

        $this->file->fileClose($f);

        if ($lineCount >= $lines) {
            $bufferLines = explode("\n", $buffer);
            $tailedLines = array_slice($bufferLines, -$lines);
            $buffer = implode("\n", $tailedLines);
        }

        return $buffer;
    }
}
