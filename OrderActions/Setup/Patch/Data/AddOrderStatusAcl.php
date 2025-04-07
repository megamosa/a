<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection as StatusCollection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;

class AddOrderStatusAcl implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var StatusCollectionFactory
     */
    private $statusCollectionFactory;

    /**
     * @var RulesCollectionFactory
     */
    private $rulesCollectionFactory;

    /**
     * @var RoleCollectionFactory
     */
    private $roleCollectionFactory;

    /**
     * @var RulesFactory
     */
    private $rulesFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param RulesCollectionFactory $rulesCollectionFactory
     * @param RoleCollectionFactory $roleCollectionFactory
     * @param RulesFactory $rulesFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StatusCollectionFactory $statusCollectionFactory,
        RulesCollectionFactory $rulesCollectionFactory,
        RoleCollectionFactory $roleCollectionFactory,
        RulesFactory $rulesFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->rulesCollectionFactory = $rulesCollectionFactory;
        $this->roleCollectionFactory = $roleCollectionFactory;
        $this->rulesFactory = $rulesFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        // Find the admin role ID
        $adminRoleId = $this->getAdminRoleId();
        
        if (!$adminRoleId) {
            // If no admin role found, we can't proceed
            return $this;
        }
        
        // Grant all status permissions to admin role
        $this->grantStatusPermissionsToAdmin($adminRoleId);
        
        return $this;
    }

    /**
     * Get admin role ID
     *
     * @return int|null
     */
    private function getAdminRoleId()
    {
        $roleCollection = $this->roleCollectionFactory->create();
        $roleCollection->addFieldToFilter('role_type', 'G')
            ->addFieldToFilter('role_name', 'Administrators');
        
        if ($roleCollection->getSize() > 0) {
            return $roleCollection->getFirstItem()->getId();
        }
        
        return null;
    }

    /**
     * Grant all status permissions to admin role
     *
     * @param int $adminRoleId
     */
    private function grantStatusPermissionsToAdmin($adminRoleId)
    {
        /** @var StatusCollection $statusCollection */
        $statusCollection = $this->statusCollectionFactory->create();
        
        // Add filter to only get statuses that are assigned to state
        $statusCollection->joinStates();
        
        foreach ($statusCollection as $status) {
            $statusCode = $status->getStatus();
            $statusLabel = $status->getLabel();
            
            if ($statusCode && $statusLabel) {
                $resourceId = 'MagoArab_OrderActions::status_' . str_replace(['-', ' '], '_', strtolower($statusCode));
                
                try {
                    // Check if the rule already exists for this role and resource
                    $existingRules = $this->rulesCollectionFactory->create()
                        ->addFieldToFilter('role_id', $adminRoleId)
                        ->addFieldToFilter('resource_id', $resourceId);
                    
                    if ($existingRules->getSize() == 0) {
                        // Create rule for admin role
                        $rule = $this->rulesFactory->create();
                        $rule->setRoleId($adminRoleId)
                            ->setResourceId($resourceId)
                            ->setPermission('allow')
                            ->save();
                    }
                } catch (\Exception $e) {
                    // Log error but don't stop the patch
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}