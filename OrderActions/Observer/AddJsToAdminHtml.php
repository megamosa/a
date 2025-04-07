<?php
/**
 * @category   MagoArab
 * @package    MagoArab_OrderActions
 */
namespace MagoArab\OrderActions\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MagoArab\OrderActions\Model\ResourceModel\ActionProvider;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class AddJsToAdminHtml implements ObserverInterface
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
     * @param ActionProvider $actionProvider
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        ActionProvider $actionProvider,
        JsonHelper $jsonHelper
    ) {
        $this->actionProvider = $actionProvider;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Add JS code to the page for filtering order actions
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $response = $observer->getEvent()->getResponse();
        
        if (!$response) {
            return;
        }
        
        // Get request
        $request = $observer->getEvent()->getRequest();
        
        if (!$request) {
            return;
        }
        
        // Check if we're in admin
        $fullActionName = $request->getFullActionName();
        
        // Only apply to sales order pages
        if (!$fullActionName || 
            strpos($fullActionName, 'sales_order') === false) {
            return;
        }
        
        // Get allowed statuses and actions
        $allowedStatuses = $this->jsonHelper->jsonEncode($this->actionProvider->getAllowedStatuses());
        $allowedActions = $this->jsonHelper->jsonEncode($this->actionProvider->getAllowedActions());
        
        // Create inline script
        $html = $response->getBody();
        $script = <<<HTML
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Define allowed statuses and actions
    var allowedStatuses = $allowedStatuses;
    var allowedActions = $allowedActions;
    
    // Function to filter dropdown menu items
    function filterActionItems() {
        // Filter submenu items (order statuses)
        document.querySelectorAll('.action-submenu > li').forEach(function(item) {
            var statusText = item.querySelector('.action-menu-item').textContent.trim();
            if (allowedStatuses.indexOf(statusText) === -1) {
                item.style.display = 'none';
            } else {
                item.style.display = '';
            }
        });
        
        // Filter main menu items
        document.querySelectorAll('.action-menu > li').forEach(function(item) {
            var actionMenuItem = item.querySelector('> .action-menu-item');
            if (!actionMenuItem) return;
            
            var actionText = actionMenuItem.textContent.trim();
            
            // Handle "Change Order Status" with submenu
            if (actionText === 'Change Order Status') {
                var visibleStatusItems = item.querySelectorAll('.action-submenu > li[style="display: none;"]').length;
                var totalStatusItems = item.querySelectorAll('.action-submenu > li').length;
                
                if (totalStatusItems > 0 && visibleStatusItems === totalStatusItems) {
                    item.style.display = 'none';
                } else {
                    item.style.display = '';
                }
            } 
            // Other main actions
            else if (allowedActions.indexOf(actionText) === -1) {
                item.style.display = 'none';
            } else {
                item.style.display = '';
            }
        });
    }
    
    // Handle Knockout.js template changes
    function observeMenuChanges() {
        // Create mutation observer to watch for dropdown changes
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length) {
                    setTimeout(filterActionItems, 100);
                }
            });
        });
        
        // Observe the document body
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Run initially after a delay
    setTimeout(filterActionItems, 1000);
    
    // Run when dropdown is clicked
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('action-select') || 
            event.target.closest('.action-select')) {
            setTimeout(filterActionItems, 100);
        }
    });
    
    // Setup observer for dynamic changes
    observeMenuChanges();
    
    // For jQuery AJAX calls
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ajaxComplete(function() {
            setTimeout(filterActionItems, 500);
        });
    }
});
</script>
HTML;
        
        // Add script to before body end
        $html = str_replace('</body>', $script . '</body>', $html);
        $response->setBody($html);
    }
}