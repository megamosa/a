<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Plugin\Sales\Order;

use MagoArab\OrderActions\Model\ResourceModel\ActionProvider;
use Magento\Framework\Phrase;

class ActionPlugin
{
    /**
     * @var ActionProvider
     */
    private $actionProvider;

    /**
     * @param ActionProvider $actionProvider
     */
    public function __construct(
        ActionProvider $actionProvider
    ) {
        $this->actionProvider = $actionProvider;
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
     * Directly filter actions in UI components
     * 
     * @param \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info|\Magento\Sales\Block\Adminhtml\Order\Grid $subject
     * @param array $result
     * @return array
     */
    public function afterGetMassActions($subject, $result)
    {
        if (empty($result)) {
            return $result;
        }

        $filteredActions = [];
        
        foreach ($result as $actionId => $actionData) {
            // Check if this is the order status action with submenu
            if ($actionId === 'order_status' && isset($actionData['actions'])) {
                $statusActions = $actionData['actions'];
                $filteredStatusActions = [];
                
                foreach ($statusActions as $statusActionId => $statusActionData) {
                    if (isset($statusActionData['label'])) {
                        $statusLabel = $this->convertToString($statusActionData['label']);
                        if ($this->actionProvider->isStatusAllowed($statusLabel)) {
                            $filteredStatusActions[$statusActionId] = $statusActionData;
                        }
                    }
                }
                
                if (!empty($filteredStatusActions)) {
                    $actionData['actions'] = $filteredStatusActions;
                    $filteredActions[$actionId] = $actionData;
                }
            } 
            // Handle regular actions
            else if (isset($actionData['label'])) {
                $label = $this->convertToString($actionData['label']);
                if ($this->actionProvider->isActionAllowed($label)) {
                    $filteredActions[$actionId] = $actionData;
                }
            } else {
                // Keep other actions that don't have a label
                $filteredActions[$actionId] = $actionData;
            }
        }
        
        return $filteredActions;
    }
}