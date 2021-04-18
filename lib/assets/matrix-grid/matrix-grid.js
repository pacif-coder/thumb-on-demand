$.widget("tod.matrixGridView", {
    options: {
        selectOnClick: true,
        selectClass: 'state-selected'
    },

    _create: function() {
        var self = this;
        if (this.options.selectOnClick) {
            $('[data-role="thumb"]', this.element).click(function(event) {
                self.onThumbClick(event);
            });
            
            this.element.addClass('thumb-on-demand-select-on-click');
        }
    },
    
    onThumbClick: function(event) {
        var target = $(event.target);
        if (target.is('a, :checkbox') || target.parents('a, :checkbox').length) {
            return;
        }

        var selection = $('[data-role="selection"]', event.currentTarget);
        if (!selection) {
            return;
        }
        
        if (selection.prop('checked')) {
            selection.prop('checked', false);
        } else {
            selection.prop('checked', true);
        }               
        
        if (selection.prop('checked')) {
            $(event.currentTarget).addClass(this.options.selectClass);
        } else {
            $(event.currentTarget).removeClass(this.options.selectClass);            
        }           
    },

    getSelectedRows: function() {
        var selection = [];
        $('[data-role="selection"]:checked', this.element).each(function() {
            selection.push($(this).val()); 
        });
        
        return selection;
    }
});