// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
(function () {
	const MfaZoneTabView = OCA.Files.DetailTabView.extend({
		id: 'mfazoneTabView',
		className: 'tab mfazoneTabView',
		getLabel: function () {
			return 'MFA Zone';
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
					<style>
						.switch {
							position: relative;
							display: inline;
						}
						
						.switch input { 
							opacity: 0;
							width: 0;
							height: 0;
						}
						
						.slider {
							position: absolute;
							cursor: pointer;
							top: 0;
							left: 0;
							right: 0;
							bottom: 0;
							width: 30px;
							height: 15px;
							background-color: #ccc;
							-webkit-transition: .4s;
							transition: .4s;
							margin-top: auto;
						}
						
						.slider:before {
							position: absolute;
							content: "";
							height: 9px;
							width: 9px;
							left: 3px;
							bottom: 3px;
							background-color: white;
							-webkit-transition: .4s;
							transition: .4s;
						}
						
						input:checked + .slider {
							background-color: #2196F3;
						}
						
						input:focus + .slider {
							box-shadow: 0 0 1px #2196F3;
						}
						
						input:checked + .slider:before {
							-webkit-transform: translateX(15px);
							-ms-transform: translateX(15px);
							transform: translateX(15px);
						}
						
						/* Rounded sliders */
						.slider.round {
							border-radius: 15px;
						}
						 
						.slider.round:before {
							border-radius: 50%;
						}
					</style>
					<span id="mfa-current-file-path" hidden></span>
                     <span style="--icon-size:36px;">
                         <label class="switch">
                             <input id="checkbox-radio-switch-mfa" type="checkbox">
							 <span class="slider round"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enforce MFA requirement
                         </label>
					 </span>
					 <br/>
					 <br/>
					 <br/>
                     <div id="need-mfa" style="--icon-size:36px;">
                         <label for="enable-2fa-button">You need to login with two factor authentication to use this feature.</label><br><br>
                         <button id="enable-2fa-button">Enable 2FA</button>
					 </div>
                </div>
                `;
			const $htmlDisabled = `
                <div style="text-align:center; word-wrap:break-word;">
					<style>
						.switch {
							position: relative;
							display: inline;
						}
						
						.switch input { 
							opacity: 0;
							width: 0;
							height: 0;
						}
						
						.slider {
							position: absolute;
							cursor: pointer;
							top: 0;
							left: 0;
							right: 0;
							bottom: 0;
							width: 30px;
							height: 15px;
							background-color: #ccc;
							-webkit-transition: .4s;
							transition: .4s;
							margin-top: auto;
						}
						
						.slider:before {
							position: absolute;
							content: "";
							height: 9px;
							width: 9px;
							left: 3px;
							bottom: 3px;
							background-color: white;
							-webkit-transition: .4s;
							transition: .4s;
						}
						
						input:checked + .slider {
							background-color: #2196F3;
						}
						
						input:focus + .slider {
							box-shadow: 0 0 1px #2196F3;
						}
						
						input:checked + .slider:before {
							-webkit-transform: translateX(15px);
							-ms-transform: translateX(15px);
							transform: translateX(15px);
						}
						
						/* Rounded sliders */
						.slider.round {
							border-radius: 15px;
						}
						
						.slider.round:before {
							border-radius: 50%;
						}
					</style>
					<span id="mfa-current-file-path" hidden></span>
                     <span style="--icon-size:36px;">
                         <label class="switch">
                             <input id="checkbox-radio-switch-mfa" type="checkbox" disabled>
							 <span class="slider round"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enforce MFA requirement
                         </label>
					 </span>
					 <br/>
					 <br/>
					 <br/>
                     <div id="need-mfa" style="--icon-size:36px;">
                         <label for="enable-2fa-button">You need to login with two factor authentication to use this feature.</label><br><br>
                         <button id="enable-2fa-button">Enable 2FA</button>
					 </div>
                </div>
                    `;
			const accessUrl = OC.generateUrl('/apps/mfazones/access'),
				data = {
					source: fileInfo.getFullPath()
				},
				_self = this;
				_fullPath = fileInfo.getFullPath();
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
			const statusUrl = OC.generateUrl('/apps/mfazones/get');
			$.ajax({
				type: 'GET',
				url: statusUrl,
				dataType: 'json',
				data: data,
				async: true,
				success: function (response) {
				    if (response.error){
					    console.log(response.error);
					    return;
					}
					self.document.getElementById('checkbox-radio-switch-mfa')
						.checked = response.status;
					console.log(response.mfa_passed);
					if (response.mfa_passed == false){
						context.$el.find('#enable-2fa-button')
							.click(context.showDialog);
					}
					if (enabled) {
						context.$el.find('#checkbox-radio-switch-mfa')
							.click(context.boxChecked);
					}
					self.document.getElementById('mfa-current-file-path')
					.textContent = _fullPath;
				},
				error: function (xhr, textStatus, thrownError) {
					console.log(xhr.status);
					console.log(textStatus);
					console.log(thrownError);
				},
			});
		},
		showDialog: function () {
			if (confirm('You must enable two factor authentication to use MFAZone app. Do you want to enable 2FA?')) {
				window.location.href = OC.generateUrl('/settings/user/security');
			}
		},
		boxChecked: function () {
			const checkBox = this;
			const setUrl = OC.generateUrl('/apps/mfazones/set'),
			data = {
				source: self.document.getElementById('mfa-current-file-path')
				.textContent,
				protect: checkBox.checked
			};
			$.ajax({
				type: 'POST',
				url: setUrl,
				dataType: 'json',
				data: data,
				async: true,
				success: function (response) {
					if (checkBox.checked === true) {
						// self.document.getElementById('mfa-check-status')
						// 	.textContent = "Protected";
					} else {
						// self.document.getElementById('mfa-check-status')
						// 	.textContent = "Not Protected";
					}
				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log(xhr.status);
					console.log(thrownError);
				},
			});
		}
	});
	OCA.mfazones = OCA.mfazones || {};
	OCA.mfazones.MfaZoneTabView = MfaZoneTabView;
})();
