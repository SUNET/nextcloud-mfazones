<!-- SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se> 
     SPDX-License-Identifier: AGPL-3.0-or-later -->
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
    <div id="need-mfa" style="--icon-size:36px;" hidden>
      <label for="enable-2fa-button">You need to login with two factor authentication to use this feature. First you
        must enable 2FA in the <a class="setting-link" :href="settingsLink">settings↗ </a> and then you need to log out
        and in again. If you have already taken these steps, and still see this message, you must log out and in
        again.</label><br><br>
    </div>
    <div id="not-owner" style="--icon-size:36px;" hidden>
      <label for="enable-2fa-button">This is a mfazone that has been shared with you, so you can not change any settings
        for it.</label><br><br>
    </div>
    <div id="not-top" style="--icon-size:36px;" hidden>
      <label for="enable-2fa-button">This is a mfazone that has been set further up in the file tree, you must go there
        to change settings for it. But take this ⚔, it is dangerous to go alone.</label><br><br>
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
      let state = await get_mfa_state(fileInfo).then(response => { return response.data }).catch(error => {
        console.log("ERROR: In update");
        console.log(error);
      })
      console.log('mfa state', state);
      var needMFA = document.getElementById('need-mfa');
      var haveMFA = document.getElementById('have-mfa');
      var notOwner = document.getElementById('not-owner');
      var notTop = document.getElementById('not-top');
      needMFA.hidden = true;
      notOwner.hidden = true;
      haveMFA.hidden = true;
      notTop.hidden = true;
      let mfa_passed = state.mfa_passed;
      let status = state.status;
      let has_access = state.has_access;
      let mfa_on_parent = state.mfa_on_parent;
      if (mfa_passed) {
        if (has_access) {
          if (mfa_on_parent) {
            notTop.hidden = false;
          } else {
            haveMFA.hidden = false;
          }
        } else {
          notOwner.hidden = false;
        }
      } else {
        needMFA.hidden = false;
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
