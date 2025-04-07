<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassStatus extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'MagoArab_OrderActions::change_status';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Update order status for selected orders
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $status = $this->getRequest()->getParam('status');
            
            // If no status provided, return to grid
            if (!$status) {
                $this->messageManager->addErrorMessage(__('Please select a status.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('sales/order/');
            }
            
            $updatedOrders = 0;
            $notUpdatedOrders = 0;
            
            foreach ($collection as $order) {
                /** @var Order $order */
                try {
                    // Change the order status
                    $comment = __('Order status changed by admin via mass action.');
                    $order->setStatus($status);
                    $order->addStatusHistoryComment($comment, $status)
                          ->setIsCustomerNotified(false);
                    $order->save();
                    $updatedOrders++;
                } catch (\Exception $e) {
                    $notUpdatedOrders++;
                    $this->messageManager->addErrorMessage(
                        __('Error updating order #%1: %2', $order->getIncrementId(), $e->getMessage())
                    );
                }
            }
            
            if ($updatedOrders) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 order(s) have been updated.', $updatedOrders)
                );
            }
            
            if ($notUpdatedOrders) {
                $this->messageManager->addErrorMessage(
                    __('A total of %1 order(s) cannot be updated.', $notUpdatedOrders)
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while updating orders: %1', $e->getMessage()));
        }
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('sales/order/');
    }
}