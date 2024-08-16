<!-- SPDX-FileCopyrightText: SUNET <kano@sunet.se> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<template>
  <NcActions>
    <span id="mfa-current-file-path" hidden></span>
    <div id="have-mfa" hidden>
      <span style="--icon-size:36px;">
        <label class="switch">
          <input id="checkbox-radio-switch-mfa" type="checkbox" @change="toggleMFAZone()">
          <span class="slider round"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Enforce MFA requirement
        </label>
      </span>
    </div>
    <br />
    <br />
    <br />
    <div id="need-mfa" style="--icon-size:36px;" hidden>
      <label for="enable-2fa-button">You need to login with two factor authentication to use this feature. First you must enable 2FA in the <a class="setting-link" :href="settingsLink">settings↗ </a> and then you need to log out and in again. If you have already taken these steps, and still see this message, you must log out and in again.</label><br><br>
    </div>
    <div id="not-owner" style="--icon-size:36px;" hidden>
      <label for="enable-2fa-button">This is a mfazone that has been shared with you, so you can not change any settings for it.</label><br><br>
    </div>
  </NcActions>
</template>
<script>
import { NcActions } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

async function get_mfa_state(fileInfo) {
  if (!fileInfo) {
    return
  }
  var path = '/';
  if (fileInfo.path != path) {
    path = fileInfo.path + '/';
  }
  let payload = {
    params: { 'source': path + fileInfo.name }
  }
  const url = generateUrl('/apps/mfazones/get');
  return axios.get(url, payload)
}

export default {
  components: {
    NcActions,
  },
  data() {
    return {
      settingsLink: generateUrl('/settings/user/security#two-factor-auth'),
    }
  },
  methods: {
    toggleMFAZone() {
      const url = generateUrl('/apps/mfazones/set');
      if (!this.fileInfo) {
        console.log('fileInfo is null');
        return
      }
      console.log('fileInfo', this.fileInfo);
      var path = '/';
      if (this.fileInfo.path !== path) {
        path = this.fileInfo.path + '/';
      }
      var element = document.getElementById('checkbox-radio-switch-mfa');
      var status = element.checked;
      console.log('status', status);
      let payload = {
        source: path + this.fileInfo.name, protect: String(status)
      }
      axios.post(url, payload).then(response => {
        console.log("INFO: In toggleMFAZone");
        console.log(response.data);
      }).catch(error => {
        console.log("ERROR: In toggleMFAZone");
        element.checked = !status;
        console.log(error);
      });
      console.log('result', result);
    },
    async update(fileInfo) {
      this.fileInfo = fileInfo;
      console.log('fileInfo', this.fileInfo);
      let state = await get_mfa_state(fileInfo).then(response => { return response.data }).catch(error => {
        console.log("ERROR: In update");
        console.log(error);
      })
      let mfa_passed = state.mfa_passed;
      let status = state.status;
      let has_access = status.has_access;
      var needMFA = document.getElementById('need-mfa');
      var haveMFA = document.getElementById('have-mfa');
      var notOwner = document.getElementById('not-owner');
      console.log('mfa state', state);
      if (mfa_passed) {
        if(has_access) {
          needMFA.hidden = true;
          notOwner.hidden = true;
          haveMFA.hidden = false;
        } else {
          needMFA.hidden = true;
          notOwner.hidden = false;
          haveMFA.hidden = true;
        }
      } else {
        needMFA.hidden = false;
        notOwner.hidden = true;
        haveMFA.hidden = true;
      }
      document.getElementById('checkbox-radio-switch-mfa').checked = status;
    },
    async resetState(fileInfo) {
      this.update(fileInfo);
    },
  },
}
</script>
<style lang="scss" scoped>
.setting-link:hover {
	text-decoration: underline;
}
</style>
