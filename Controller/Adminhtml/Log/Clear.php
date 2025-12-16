<?php
declare(strict_types=1);

namespace GardenLawn\LogViewer\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;

class Clear extends Action implements HttpPostActionInterface
{
    public const string ADMIN_RESOURCE = 'GardenLawn_LogViewer::clear';

    /**
     * @var File
     */
    private File $file;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @param Context $context
     * @param File $file
     * @param DirectoryList $directoryList
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context       $context,
        File          $file,
        DirectoryList $directoryList,
        JsonFactory   $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        try {
            $filePath = $this->getRequest()->getPost('file_path');

            if (!$filePath) {
                throw new LocalizedException(__('File path is missing.'));
            }

            $logDirectory = $this->directoryList->getPath(DirectoryList::LOG);

            // Validate that the requested file path is within the log directory
            if (strpos($filePath, $logDirectory) !== 0 || !is_file($filePath)) {
                throw new LocalizedException(__('Invalid file path or file does not exist.'));
            }

            // Clear the file content
            $this->file->filePutContents($filePath, '');

            $this->messageManager->addSuccessMessage(__('Log file "%1" has been cleared.', basename($filePath)));
            return $result->setData(['success' => true]);

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while clearing the file.'));
            return $result->setData(['success' => false, 'message' => __('An error occurred while clearing the file.')]);
        }
    }
}
