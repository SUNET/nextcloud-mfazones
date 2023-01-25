(function() {
    const MfaZoneTabView = OCA.Files.DetailTabView.extend({
        id: 'mfazoneTabView',
        className: 'tab mfazoneTabView',

        getLabel: function () {
            return 'MfaZone';
        },

        getIcon: function () {
            return 'icon-category-security';
        },

        render: function () {
            const fileInfo = this.getFileInfo();
            const $html = `
            <div style="text-align:center; word-wrap:break-word;">
                 <span class="checkbox-radio-switch checkbox-radio-switch-switch checkbox-radio-switch--checked" style="--icon-size:36px;">
                     <label for="checkbox-radio-switch-mfa" class="checkbox-radio-switch__label">
                         <input id="checkbox-radio-switch-mfa" type="checkbox" class="checkbox-radio-switch__input" value="">
                         <span aria-hidden="true" role="img" class="material-design-icon toggle-switch-icon checkbox-radio-switch__icon">
                             <svg fill="currentColor" width="36" height="36" viewBox="0 0 24 24" class="material-design-icon__svg">
                                 <path d="M17,7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7M17,15A3,3 0 0,1 14,12A3,3 0 0,1 17,9A3,3 0 0,1 20,12A3,3 0 0,1 17,15Z">
                
                                 </path>
                             </svg>
                         </span> <span class="mfa-check-status"></span>
                     </label>
                 </span>
            </div>
                `;


            if (fileInfo) {
                this.$el.html($html);

                const url = OC.generateUrl('/apps/mfaverifiedzone/get'),
                    data = {source: fileInfo.getFullPath()},
                    _self = this;
                $.ajax({
                    type: 'GET',
                    url: url,
                    dataType: 'json',
                    data: data,
                    async: true,
                    success: function (data) {
                        _self.generateStatusText(data);
                    }
                });
            }
        },

        generateStatusText: function (value) {
            const status = value === true ? "Required" : "Not required";
            this.$el.find('.mfa-check-status').text(status)
        },
        
        boxChecked: function () {
            const checkBox = this.$el.find("#checkbox-radio-switch-mfa");
            if (checkBox.checked === true) {
            this.generateStatusText(true);
        } else {
            this.generateStatusText(false);
        }
    }
    });

    OCA.MfaVerifiedZone = OCA.MfaVerifiedZone || {};

    OCA.MfaVerifiedZone.MfaZoneTabView = MfaZoneTabView;
})();
