<?php
declare(strict_types=1);

namespace GardenLawn\LogViewer\Controller\Adminhtml\Log;

use Laminas\Http\Response;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
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
     * @param Context $context
     * @param RequestInterface $request
     * @param File $file
     */
    public function __construct(
        Context          $context,
        RequestInterface $request,
        File             $file
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->file = $file;
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        try {
            $filePath = $this->request->getPost('file_path');

            if (!$filePath) {
                throw new LocalizedException(__('File path is missing.'));
            }

            $content = $this->file->fileGetContents($filePath);
            return $this->getResponse()
                ->setHttpResponseCode(Response::STATUS_CODE_200)
                ->setHeader('Content-Type', 'application/text', true)
                ->setBody($content);

        } catch (LocalizedException) {
            $this->messageManager->addErrorMessage(__('An error occurred while downloading the file.'));
        }

        return $this->_redirect('*/*/');
    }
}
