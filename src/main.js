/**
 * @copyright Copyright (c) 2024 Michiel de Jong <michiel@unhosted.org>
 *
 * @author Michiel de Jong <michiel@unhosted.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


import MfaVerifiedValue from './Checks/MfaVerifiedValue'

const appId = 'mfazones'

// copied from https://github.com/nextcloud/flow_webhooks/blob/d06203fa3cc6a5dc83b6f08ab7dd82d61585d334/src/main.js#L27
if (typeof window.OCA.WorkflowEngine === 'undefined') {
	window.OCA.WorkflowEngine.registerCheck({
		class: 'OCA\\mfazones\\Check\\MfaVerified',
		name: t(appId, 'multi-factor authentication'),
		operators: [
			{ operator: 'is', name: t(appId, 'is verified') },
			{ operator: '!is', name: t(appId, 'is not verified') },
		],
		component: MfaVerifiedValue,
	});
}
var mfazoneFileListPlugin = {
    attach: function(fileList) {
        fileList.registerTabView(new OCA.mfazones.MfaZoneTabView());
	    // Uncomment this to display an 'MFA Zone' label on MFA zones in the files list in the files app.
		// You will also need to uncomment the DAV plugin in the appinfo/info.xml file, and apply the changes
		// from https://github.com/pondersource/server/commits/white-list-mfa-zone-dav-attribute/
		// to your server:
		// fileList.fileActions.registerAction({
		// 	name: 'mfa',
		// 	displayName: 'MFA Zone',
		// 	type: 1,
		// 	mime: 'all',
		// 	permissions: OC.PERMISSION_NONE,
		// 	iconClass: 'icon-category-security',
		// 	actionHandler: function(fileName, context) {
		// 	const statusUrl = OC.generateUrl('/apps/mfazones/getMfaStatus');
		// 	$.ajax({
		// 		type: 'GET',
		// 		url: statusUrl,
		// 		dataType: 'json',
		// 		async: true,
		// 		success: function (response) {
		// 		if (response.error){
		// 			console.log(response.error);
		// 			return;
		// 		}
		// 		if (response.mfa_passed !== true) {
		// 			const choice = confirm('This folder requires Multi Factor Authentication. Do you want to enable it for your account?')
		// 			if (choice) {
		// 			window.location.href = OC.generateUrl('/settings/user/security');
		// 			}
		// 		} else {
		// 			alert('You have already enabled Multi Factor Authentication for your account.');
		// 		}
		// 		}
		// 	}); 
        // },
        // });
    }
};
OC.Plugins.register('OCA.Files.FileList', mfazoneFileListPlugin);

(function () {
	function renderHTML(enabled) {
		return `
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
						 <input id="checkbox-radio-switch-mfa" type="checkbox" ${enabled ? '' : 'disabled'}>
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
	}
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
			if (!fileInfo.isDirectory()) {
				this.$el.html(`<div>MFA Zones are currently disabled for files.</div>`);
				console.log("Not a directory, MFA zone detail tab disabled.");
				return;
			}
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
					_self.addHtml(_self, data, response.access);
				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log(xhr.status);
					console.log(thrownError);
				},
			});
		},
		addHtml: function (context, data, enabled) {
			context.$el.html(renderHTML(enabled));
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
					if (response.mfa_passed){
						context.$el.find('#need-mfa').hide();
					} else {
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
