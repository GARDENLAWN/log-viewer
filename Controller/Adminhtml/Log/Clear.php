<?php
declare(strict_types=1);

namespace GardenLawn\LogViewer\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;

class Clear extends Action
{
    public const string ADMIN_RESOURCE = 'GardenLawn_LogViewer::clear';

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
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param File $file
     * @param DirectoryList $directoryList
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context          $context,
        RequestInterface $request,
        File             $file,
        DirectoryList    $directoryList,
        JsonFactory      $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $result = $this->resultJsonFactory->create();
        try {
            $filePath = $this->request->getPost('file_path');

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
