<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Plugin\Ui;

use Magento\Ui\Component\MassAction;
use MagoArab\OrderActions\Model\ResourceModel\ActionProvider;
use Magento\Framework\Phrase;

class MassActionPlugin
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
     * Filter the actions in the UI component
     *
     * @param MassAction $subject
     * @param void $result
     * @return void
     */
    public function afterPrepare(MassAction $subject, $result)
    {
        $config = $subject->getData('config');
        
        if (isset($config['actions'])) {
            $filteredActions = [];
            
            foreach ($config['actions'] as $actionId => $actionConfig) {
                // Handle the 'Change Order Status' action with submenu
                if (is_array($actionConfig) && isset($actionConfig['type']) && $actionConfig['type'] === 'order_status' && isset($actionConfig['actions'])) {
                    $filteredSubactions = [];
                    
                    foreach ($actionConfig['actions'] as $subactionId => $subactionConfig) {
                        if (isset($subactionConfig['label'])) {
                            $label = $this->convertToString($subactionConfig['label']);
                            if ($this->actionProvider->isStatusAllowed($label)) {
                                $filteredSubactions[$subactionId] = $subactionConfig;
                            }
                        }
                    }
                    
                    if (!empty($filteredSubactions)) {
                        $actionConfig['actions'] = $filteredSubactions;
                        $filteredActions[$actionId] = $actionConfig;
                    }
                } 
                // Handle regular actions 
                else if (isset($actionConfig['label'])) {
                    $label = $this->convertToString($actionConfig['label']);
                    if ($this->actionProvider->isActionAllowed($label)) {
                        $filteredActions[$actionId] = $actionConfig;
                    }
                }
                // Include any action not matching our criteria
                else {
                    $filteredActions[$actionId] = $actionConfig;
                }
            }
            
            $config['actions'] = $filteredActions;
            $subject->setData('config', $config);
        }
        
        return $result;
    }
}