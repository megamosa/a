<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Plugin\Sales\Ui\Component;

use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;
use Magento\Ui\Component\MassAction as UiMassAction;
use Magento\Framework\AuthorizationInterface;

class MassAction
{
    /**
     * @var StatusCollectionFactory
     */
    private $statusCollectionFactory;
    
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        StatusCollectionFactory $statusCollectionFactory,
        AuthorizationInterface $authorization
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->authorization = $authorization;
    }

    /**
     * Add order statuses to mass actions
     *
     * @param UiMassAction $subject
     * @param array $result
     * @return array
     */
    public function afterPrepare(UiMassAction $subject, $result)
    {
        $config = $subject->getData('config');
        if (isset($config['actions']) && $subject->getContext()->getNamespace() === 'sales_order_grid') {
            // Find the change status action
            foreach ($config['actions'] as $actionKey => $actionConfig) {
                if (isset($actionConfig['type']) && $actionConfig['type'] === 'status') {
                    // Add our custom action to change status
                    $statusOptions = $this->getOrderStatusOptions();
                    if (!empty($statusOptions)) {
                        $config['actions'][] = [
                            'type' => 'magoarab_status',
                            'label' => __('Change Order Status (MagoArab)'),
                            'url' => 'magoarab/order/massStatus',
                            'status_field' => 'status',
                            'options' => $statusOptions
                        ];
                    }
                    break;
                }
            }
            $subject->setData('config', $config);
        }
        
        return $result;
    }
    
    /**
     * Get order status options for mass action
     *
     * @return array
     */
    private function getOrderStatusOptions()
    {
        if (!$this->authorization->isAllowed('MagoArab_OrderActions::change_status')) {
            return [];
        }
        
        $options = [];
        $collection = $this->statusCollectionFactory->create();
        $collection->joinStates();
        
        foreach ($collection as $status) {
            $statusCode = $status->getStatus();
            $statusLabel = $status->getLabel();
            
            // Create resource ID based on status code
            $resourceId = 'MagoArab_OrderActions::status_' . str_replace(['-', ' '], '_', strtolower($statusCode));
            
            // Check if status is allowed
            if ($this->authorization->isAllowed($resourceId) || $this->authorization->isAllowed('MagoArab_OrderActions::order_statuses')) {
                $options[] = [
                    'value' => $statusCode,
                    'label' => $statusLabel,
                ];
            }
        }
        
        return $options;
    }
}