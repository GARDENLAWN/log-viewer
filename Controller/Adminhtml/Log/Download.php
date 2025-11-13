<?php
declare(strict_types=1);

namespace GardenLawn\LogViewer\Controller\Adminhtml\Log;

use Laminas\Http\Response;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;

class Download extends Action
{
    public const string ADMIN_RESOURCE = 'GardenLawn_LogViewer::download';

    /**
     * @var File
     */
    private File $file;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context          $context,
        RequestInterface $request,
        File             $file,
        DirectoryList    $directoryList
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        try {
            $filePath = $this->request->getPost('file_path');
            $lines = (int) $this->request->getPost('lines', 100); // Default to 100 lines

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
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->getResponse()->setBody($e->getMessage()); // Return error message to AJAX
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while downloading the file.'));
            return $this->getResponse()->setBody(__('An error occurred while downloading the file.')); // Return error message to AJAX
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
    private function tailFile(string $filepath, int $lines = 100): string
    {
        $f = $this->file->fileOpen($filepath, 'r');
        if (!$f) {
            return '';
        }

        $buffer = [];
        $lineCounter = 0;
        $pos = -1;

        while ($lineCounter < $lines) {
            if ($this->file->fileSeek($f, $pos, SEEK_END) === -1) {
                // Reached beginning of file
                break;
            }
            $char = $this->file->fileRead($f, 1);
            if ($char === "\n") {
                $lineCounter++;
            }
            array_unshift($buffer, $char);
            $pos--;
        }
        $this->file->fileClose($f);

        // Remove partial first line if it's not the beginning of the file
        if ($lineCounter >= $lines && $buffer[0] !== "\n") {
            while (count($buffer) > 0 && array_shift($buffer) !== "\n") {
                // Remove characters until the next newline
            }
        }

        return implode('', $buffer);
    }
}
