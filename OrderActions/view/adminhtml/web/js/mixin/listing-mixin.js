define([], function() {
    'use strict';
    
    // List of allowed statuses and actions - will be replaced by PHP
    var allowedStatuses = ['__ALLOWED_STATUSES_PLACEHOLDER__'];
    var allowedActions = ['__ALLOWED_ACTIONS_PLACEHOLDER__'];
    
    return function(target) {
        return target.extend({
            /**
             * Override the initActions method to filter the actions
             */
            initActions: function() {
                // Call the original method
                this._super();
                
                // Filter the actions
                var filteredActions = [];
                
                this.actions().forEach(function(action) {
                    // Check if it's the status change action
                    if (action.type === 'order_status' && action.actions) {
                        // Filter sub-actions based on allowed statuses
                        var filteredSubActions = [];
                        
                        action.actions.forEach(function(subAction) {
                            if (allowedStatuses.indexOf(subAction.label) !== -1) {
                                filteredSubActions.push(subAction);
                            }
                        });
                        
                        // Only include the parent action if it has child actions
                        if (filteredSubActions.length) {
                            action.actions = filteredSubActions;
                            filteredActions.push(action);
                        }
                    } else if (allowedActions.indexOf(action.label) !== -1) {
                        // Include other actions if allowed
                        filteredActions.push(action);
                    }
                });
                
                // Replace original actions with filtered ones
                this.actions(filteredActions);
            }
        });
    };
});