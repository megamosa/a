<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Model\ResourceModel;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection as StatusCollection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;

class ActionProvider
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var StatusCollectionFactory
     */
    private $statusCollectionFactory;

    /**
     * @var array
     */
    private $statusMapping;

    /**
     * @var array
     */
    private $actionMapping = [
        'Print PDF Orders' => 'MagoArab_OrderActions::print_pdf',
        'Print Invoices' => 'MagoArab_OrderActions::print_invoices',
        'Print Packing Slips' => 'MagoArab_OrderActions::print_packing',
        'Print Credit Memos' => 'MagoArab_OrderActions::print_credit_memos',
        'Print All' => 'MagoArab_OrderActions::print_all',
        'Print Shipping Labels' => 'MagoArab_OrderActions::print_shipping',
        'Cancel' => 'MagoArab_OrderActions::cancel',
        'Hold' => 'MagoArab_OrderActions::hold',
        'Unhold' => 'MagoArab_OrderActions::unhold',
        'Delete' => 'MagoArab_OrderActions::delete',
        'Change Order Status' => 'MagoArab_OrderActions::change_status',
        'Print Actions' => 'MagoArab_OrderActions::print_actions',
        'Order Management' => 'MagoArab_OrderActions::order_management',
    ];

    /**
     * @param AuthorizationInterface $authorization
     * @param StatusCollectionFactory $statusCollectionFactory
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StatusCollectionFactory $statusCollectionFactory
    ) {
        $this->authorization = $authorization;
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * Load and cache status mapping from database
     *
     * @return array
     */
    private function getStatusMapping()
    {
        if ($this->statusMapping === null) {
            $this->statusMapping = [];
            
            /** @var StatusCollection $collection */
            $collection = $this->statusCollectionFactory->create();
            $collection->joinStates();
            
            foreach ($collection as $status) {
                $statusLabel = $status->getLabel();
                $statusCode = $status->getStatus();
                
                if ($statusLabel && $statusCode) {
                    $resourceId = 'MagoArab_OrderActions::status_' . str_replace(['-', ' '], '_', strtolower($statusCode));
                    $this->statusMapping[$this->convertToString($statusLabel)] = $resourceId;
                }
            }
        }
        
        return $this->statusMapping;
    }

    /**
     * Convert value to string if it's a Phrase object
     *
     * @param mixed $value
     * @return string
     */
    private function convertToString($value)
    {
        if ($value instanceof Phrase) {
            return (string)$value;
        }
        return (string)$value;
    }

    /**
     * Check if status is allowed - now always returns true since we manage 
     * permissions via the parent 'Order Statuses' permission
     *
     * @param string|Phrase $status
     * @return bool
     */
    public function isStatusAllowed($status)
    {
        // Check if user has general permission for order statuses
        if ($this->authorization->isAllowed('MagoArab_OrderActions::order_statuses')) {
            return true;
        }
        
        // Otherwise check specific status
        $statusString = $this->convertToString($status);
        $statusMapping = $this->getStatusMapping();
        
        if (!isset($statusMapping[$statusString])) {
            return true; // If not configured, allow by default
        }

        return $this->authorization->isAllowed($statusMapping[$statusString]);
    }

    /**
     * Check if action is allowed
     *
     * @param string|Phrase $action
     * @return bool
     */
    public function isActionAllowed($action)
    {
        $actionString = $this->convertToString($action);
        
        if (!isset($this->actionMapping[$actionString])) {
            // Special handling for third-party extensions
            if ($actionString === 'Create Invoice' || 
                $actionString === 'Create Shipment' || 
                $actionString === 'Create Invoice and Shipment' || 
                $actionString === 'Add Order Comments' || 
                $actionString === 'Send Tracking Information') {
                return false; // Block these specific actions if not explicitly allowed
            }
            
            return true; // Allow other actions by default
        }

        return $this->authorization->isAllowed($this->actionMapping[$actionString]);
    }

    /**
     * Get all statuses that are allowed
     *
     * @return array
     */
    public function getAllowedStatuses()
    {
        // If user has general permission for all statuses, return all statuses
        if ($this->authorization->isAllowed('MagoArab_OrderActions::order_statuses')) {
            $allStatuses = [];
            $collection = $this->statusCollectionFactory->create();
            $collection->joinStates();
            
            foreach ($collection as $status) {
                $allStatuses[] = $status->getLabel();
            }
            
            return $allStatuses;
        }
        
        // Otherwise return only statuses with specific permissions
        $allowed = [];
        $statusMapping = $this->getStatusMapping();
        
        foreach ($statusMapping as $status => $resource) {
            if ($this->authorization->isAllowed($resource)) {
                $allowed[] = $status;
            }
        }
        
        return $allowed;
    }

    /**
     * Get all actions that are allowed
     *
     * @return array
     */
    public function getAllowedActions()
    {
        $allowed = [];
        foreach ($this->actionMapping as $action => $resource) {
            if ($this->authorization->isAllowed($resource)) {
                $allowed[] = $action;
            }
        }
        
        return $allowed;
    }
}