define([
    'jquery',
    'mage/storage',
    'Magento_Ui/js/modal/alert'
], function ($, storage, alert) {
    'use strict';

    /**
     * MagoArab OrderActions permissions mixin
     */
    return function (target) {
        return target.extend({
            defaults: {
                magoarabAllowedActions: [],
                magoarabAllowedStatuses: []
            },
            
            /**
             * Initialize mixin
             */
            initialize: function () {
                var self = this;
                
                // Call parent initialize
                this._super();
                
                // Load permissions data
                this.loadPermissionsData();
                
                return this;
            },
            
            /**
             * Load permissions from server
             */
            loadPermissionsData: function() {
                var self = this;
                
                // Send AJAX request to get permissions
                storage.get('magoarab/permissions/load').done(function(response) {
                    if (response && response.allowed_actions) {
                        self.magoarabAllowedActions = response.allowed_actions;
                        self.magoarabAllowedStatuses = response.allowed_statuses;
                        
                        // Filter actions after permissions are loaded
                        self.filterActionsByPermissions();
                    }
                }).fail(function() {
                    // Silent fail - don't block UI
                });
            },
            
            /**
             * Filter actions by permissions
             */
            filterActionsByPermissions: function() {
                var self = this;
                
                // If no data loaded yet, do nothing
                if (!this.magoarabAllowedActions.length && !this.magoarabAllowedStatuses.length) {
                    return;
                }
                
                // Filter the actions
                var filteredActions = [];
                
                this.actions().forEach(function(action) {
                    // Check if it's the status change action
                    if (action.type === 'order_status' && action.actions) {
                        // Filter sub-actions (statuses)
                        var filteredStatuses = [];
                        
                        action.actions.forEach(function(statusAction) {
                            if (self.magoarabAllowedStatuses.indexOf(statusAction.label) !== -1) {
                                filteredStatuses.push(statusAction);
                            }
                        });
                        
                        // Only keep the parent action if it has valid children
                        if (filteredStatuses.length) {
                            // Create new action object to avoid reference issues
                            var newAction = $.extend(true, {}, action);
                            newAction.actions = filteredStatuses;
                            filteredActions.push(newAction);
                        }
                    } else if (self.magoarabAllowedActions.indexOf(action.label) !== -1) {
                        filteredActions.push(action);
                    }
                });
                
                // Replace actions with filtered list
                this.actions(filteredActions);
            }
        });
    };
});