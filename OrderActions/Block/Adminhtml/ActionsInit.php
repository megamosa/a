<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use MagoArab\OrderActions\Model\ResourceModel\ActionProvider;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class ActionsInit extends Template
{
    /**
     * @var ActionProvider
     */
    private $actionProvider;

    /**
     * @var CollectionFactory
     */
    private $statusCollectionFactory;

    /**
     * @param Context $context
     * @param ActionProvider $actionProvider
     * @param CollectionFactory $statusCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ActionProvider $actionProvider,
        CollectionFactory $statusCollectionFactory,
        array $data = []
    ) {
        $this->actionProvider = $actionProvider;
        $this->statusCollectionFactory = $statusCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get allowed statuses
     *
     * @return array
     */
    public function getAllowedStatuses()
    {
        // If we have access to the general Order Statuses resource, return all statuses
        if ($this->_authorization->isAllowed('MagoArab_OrderActions::order_statuses')) {
            $statuses = [];
            $collection = $this->statusCollectionFactory->create();
            foreach ($collection as $status) {
                $statuses[] = $status->getLabel();
            }
            return $statuses;
        }
        
        return $this->actionProvider->getAllowedStatuses();
    }

    /**
     * Get allowed actions
     *
     * @return array
     */
    public function getAllowedActions()
    {
        return $this->actionProvider->getAllowedActions();
    }
}