<template>
	<NcActions>
    <span id="mfa-current-file-path" hidden></span>
     <span style="--icon-size:36px;">
					 <label class="switch">
						 <input id="checkbox-radio-switch-mfa" type="checkbox" @change="actionMFA()" >
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
	</NcActions>
</template>
<script>
import { NcActions } from '@nextcloud/vue'

export default {
	components: {
    NcActions,
	},
	methods: {
		actionMFA() {
			alert('MFA Zone')
		},
    update(fileInfo) {
			this.fileInfo = fileInfo
		},
    resetState() {
		},
	},
}
</script>
