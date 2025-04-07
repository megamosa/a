<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Block\Adminhtml\Config;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MagoArab\OrderActions\Model\ResourceModel\ActionProvider;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Provider extends Template
{
    /**
     * @var ActionProvider
     */
    protected $actionProvider;
    
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;
    
    /**
     * @param Context $context
     * @param ActionProvider $actionProvider
     * @param JsonHelper $jsonHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ActionProvider $actionProvider,
        JsonHelper $jsonHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->actionProvider = $actionProvider;
        $this->jsonHelper = $jsonHelper;
    }
    
    /**
     * Get the action provider
     *
     * @return ActionProvider
     */
    public function getActionProvider()
    {
        return $this->actionProvider;
    }
}