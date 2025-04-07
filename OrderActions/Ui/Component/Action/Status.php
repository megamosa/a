<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Ui\Component\Action;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Action\Action;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Framework\AuthorizationInterface;

class Status extends Action
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var CollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @param ContextInterface $context
     * @param UrlInterface $urlBuilder
     * @param CollectionFactory $statusCollectionFactory
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UrlInterface $urlBuilder,
        CollectionFactory $statusCollectionFactory,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->authorization = $authorization;
        parent::__construct($context, $components, $data);
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $config = $this->getConfiguration();
        
        if (!isset($config['options'])) {
            $config['options'] = $this->getStatusOptions();
            $this->setData('config', $config);
        }
        
        parent::prepare();
    }

    /**
     * Get order status options
     *
     * @return array
     */
    protected function getStatusOptions()
    {
        $options = [];
        $collection = $this->statusCollectionFactory->create();
        
        foreach ($collection as $status) {
            $options[] = [
                'value' => $status->getStatus(),
                'label' => $status->getLabel()
            ];
        }
        
        return $options;
    }
}