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
