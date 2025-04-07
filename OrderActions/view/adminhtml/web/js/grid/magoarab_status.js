define([
    'jquery',
    'Magento_Ui/js/grid/massactions/abstract'
], function ($, Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            type: 'magoarab_status',
            template: 'ui/grid/actions/status',
            statusField: 'status'
        },

        /**
         * Default action callback
         */
        defaultCallback: function () {
            var itemsType = this.excludeMode ? 'excluded' : 'selected',
                selections = {};

            selections[itemsType] = this.getSelections()[itemsType];
            selections[this.statusField] = this.status;

            if (this.validateSelections(selections)) {
                this.submit(selections);
            }
        },

        /**
         * After options rendered callback
         * @param {Object} elem
         */
        afterOptionsRender: function (elem) {
            this._super();
            
            var statusElems = elem.querySelectorAll('input[name="status"]');
            
            for (var i=0; i < statusElems.length; i++) {
                statusElems[i].addEventListener('change', function (event) {
                    this.status = event.target.value;
                }.bind(this));
            }
        }
    });
});