(function() {
    var MfaZoneTabView = OCA.Files.DetailTabView.extend({
        id: 'mfazoneTabView',
        className: 'tab mfazoneTabView',

        getLabel: function() {
            return 'MfaZone';
        },

        getIcon: function() {
            return 'icon-details';
        },

        render: function() {
            var fileInfo = this.getFileInfo();
            
            if (fileInfo) {
                this.$el.html('<div style="text-align:center; word-wrap:break-word;"><p><br><br>'
                    + '<input type="button">Restrict</input>'
                    + '</p></div>');

                /*var url = OC.generateUrl('/apps/mfazone/get'),
                    data = {source: fileInfo.getFullPath()},
                    _self = this;
                $.ajax({
                    type: 'GET',
                    url: url,
                    dataType: 'json',
                    data: data,
                    async: true,
                    success: function(data) {
                        _self.updateDisplay(data);
                    }
                });*/
            }
        },
    });

    OCA.MfaVerifiedZone = OCA.MfaVerifiedZone || {};

    OCA.MfaVerifiedZone.MfaZoneTabView = MfaZoneTabView;
})();
