<?php
declare(strict_types=1);

namespace GardenLawn\LogViewer\Controller\Adminhtml\Log;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Glob;

class ClearAll extends Action
{
    public const string ADMIN_RESOURCE = 'GardenLawn_LogViewer::clear_all';

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
    )
    {
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
            $logDirectory = $this->directoryList->getPath(DirectoryList::LOG);
            $files = Glob::glob($logDirectory . '/*.log');

            foreach ($files as $filePath) {
                if (is_file($filePath)) {
                    $this->file->filePutContents($filePath, '');
                }
            }

            $this->messageManager->addSuccessMessage(__('All log files have been cleared.'));
            return $result->setData(['success' => true]);

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while clearing log files.'));
            return $result->setData(['success' => false, 'message' => __('An error occurred while clearing log files.')]);
        }
    }
}
