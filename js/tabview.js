(function () {
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
			if (!fileInfo) {
				console.error("File info not found!");
				return;
			}
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
                         </span> <span id="mfa-check-status"></span>
                     </label>
                 </span>
            </div>
                `;
			const $htmlDisabled = `
                <div style="text-align:center; word-wrap:break-word;">
                     <span class="checkbox-radio-switch checkbox-radio-switch-switch checkbox-radio-switch--checked" style="--icon-size:36px;">
                         <label for="checkbox-radio-switch-mfa" class="checkbox-radio-switch__label">
                             <input id="checkbox-radio-switch-mfa" type="checkbox" disabled class="checkbox-radio-switch__input" value="">
                             <span aria-hidden="true" role="img" class="material-design-icon toggle-switch-icon checkbox-radio-switch__icon">
                                 <svg fill="currentColor" width="36" height="36" viewBox="0 0 24 24" class="material-design-icon__svg">
                                     <path d="M17,7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7M17,15A3,3 0 0,1 14,12A3,3 0 0,1 17,9A3,3 0 0,1 20,12A3,3 0 0,1 17,15Z">
                    
                                     </path>
                                 </svg>
                             </span> <span id="mfa-check-status"></span>
                         </label>
                     </span>
                </div>
                    `;
			const accessUrl = OC.generateUrl('/apps/mfaverifiedzone/access'),
				data = {
					source: fileInfo.getFullPath()
				},
				_self = this;
			$.ajax({
				type: 'GET',
				url: accessUrl,
				dataType: 'json',
				data: data,
				async: true,
				success: function (response) {
					console.log(response);
					if (response.access === false) {
						_self.addHtml(_self, $htmlDisabled, data, false);
					} else {
						_self.addHtml(_self, $html, data, true);
					}
				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log(xhr.status);
					console.log(thrownError);
				},
			});
		},
		addHtml: function (context, html, data, enabled) {
			context.$el.html(html);
			const statusUrl = OC.generateUrl('/apps/mfaverifiedzone/get');
			$.ajax({
				type: 'GET',
				url: statusUrl,
				dataType: 'json',
				data: data,
				async: true,
				success: function (response) {
					self.document.getElementById('checkbox-radio-switch-mfa')
						.checked = response.status;
					if (enabled) {
						context.$el.find('#checkbox-radio-switch-mfa')
							.click(context.boxChecked);
					}
					context.generateStatusText(response.status);
				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log(xhr.status);
					console.log(thrownError);
				},
			});
		},
		generateStatusText: function (value) {
			const status = value === true ? "Required" : "Not required";
			this.$el.find('#mfa-check-status')
				.text(status)
		},
		boxChecked: function () {
			const checkBox = this;
			const setUrl = OC.generateUrl('/apps/mfaverifiedzone/set'),
			data = {
				source: fileInfo.getFullPath(),
				protect: checkBox.checked
			},
			_self = self;
			$.ajax({
				type: 'POST',
				url: setUrl,
				dataType: 'json',
				data: data,
				async: true,
				success: function (response) {
					if (checkBox.checked === true) {
						self.document.getElementById('mfa-check-status')
							.textContent = "Protected";
					} else {
						self.document.getElementById('mfa-check-status')
							.textContent = "Not Protected";
					}
				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log(xhr.status);
					console.log(thrownError);
				},
			});
		}
	});
	OCA.MfaVerifiedZone = OCA.MfaVerifiedZone || {};
	OCA.MfaVerifiedZone.MfaZoneTabView = MfaZoneTabView;
})();