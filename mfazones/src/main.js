/** SPDX-FileCopyrightText: 2024 Pondersource <michiel@pondersource.com>
 *  SPDX-FileCopyrightText: 2024 Micke Nordin <kano@sunet.se>
 *  SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import MFATabEmpty from './views/MFATabEmpty.vue'
import MFATab from './views/MFATab.vue'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import {
  FileAction,
  registerFileAction,
} from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
Vue.prototype.t = t
const appId = 'mfazones'

const View = Vue.extend(MFATab)
let MFATabInstance = null

if ((typeof window.OCA !== 'undefined') && typeof window.OCA.WorkflowEngine !== 'undefined') {
  window.OCA.WorkflowEngine.registerCheck({
    class: 'OCA\\mfazones\\Check\\MfaVerified',
    name: t(appId, 'MFA Status'),
    operators: [
      { operator: 'is', name: t(appId, 'is verified') },
      { operator: '!is', name: t(appId, 'is not verified') },
    ],
    component: MFATabEmpty,
  });
  window.OCA.WorkflowEngine.registerCheck({
    class: 'OCA\\mfazones\\Check\\FileSystemTag',
    name: t(appId, 'MFA Zone'),
    operators: [
      { operator: 'is', name: t(appId, 'is enabled') },
      { operator: '!is', name: t(appId, 'is not enabled') },
    ],
    component: MFATabEmpty,
  });
}

window.addEventListener('DOMContentLoaded', function() {
  if (OCA.Files && OCA.Files.Sidebar) {
    const mfaTab = new OCA.Files.Sidebar.Tab({
      id: 'mfazone',
      name: t('mfazone', 'MFA Zone'),
      icon: 'icon-category-security',

      async mount(el, fileInfo, context) {
        if (MFATabInstance) {
          MFATabInstance.$destroy()
        }
        MFATabInstance = new View({
          // Better integration with vue parent component
          parent: context,
        })
        // Only mount after we have all the info we need
        MFATabInstance.update(fileInfo)
        MFATabInstance.$mount(el)

      },
      update(fileInfo) {
        MFATabInstance.update(fileInfo)
      },
      destroy() {
        MFATabInstance.$destroy()
        MFATabInstance = null
      },
      enabled(fileInfo) {
        return (fileInfo.type === 'dir')
      },
    })
    OCA.Files.Sidebar.registerTab(mfaTab)
  }
});

export const getInfoLabel = () => {
  return 'MFA Zone';
}
export const getSvg = () => {
  return `<svg width="16.898" height="20" version="1.1" viewBox="0 0 4.4709 5.2916" xmlns="http://www.w3.org/2000/svg">
      <g transform="translate(-47.692 -117.23)">
      <rect x="47.688" y="119.33" width="4.4709" height="3.1957" ry="1.0867" fill="#666" stroke-width=".030235"/>
      <ellipse cx="49.954" cy="119.11" rx="1.6826" ry="1.6549" fill="none" stroke="#666" stroke-width=".44167"/>
      <ellipse cx="49.921" cy="119.29" rx="1.0299" ry="1.027" fill="none" stroke-width=".030235"/>
      <text x="48.722206" y="121.35229" fill="#ffffff" fill-opacity=".0011489" font-size="1.29px" stroke="#ffffff" stroke-width=".034282" xml:space="preserve"><tspan x="48.722206" y="121.35229" fill="#ffffff" fill-opacity=".99993" font-family="'Liberation Mono'" font-size="1.29px" stroke="#ffffff" stroke-width=".034282">MFA</tspan></text>
      <text transform="matrix(.030235 0 0 .030235 42.238 103.84)" fill="#000000" fill-opacity=".0011489" stroke="#000000" stroke-width="1.1339" style="shape-inside:url(#rect2692);white-space:pre" xml:space="preserve"/>
      </g>
      </svg>
      `
}

const inlineAction = new FileAction({
  id: 'mfa_inline',
  title: (nodes) => nodes.length === 1 ? getInfoLabel() : '',
  inline: () => true,
  displayName: () => '',
  iconSvgInline: () => '',
  exec: async () => null,
  order: -10,
  async renderInline(node) {
    let payload = {
      params: { source: node.path }
    }
    const url = generateUrl('/apps/mfazones/get');
    let result = await axios.get(url, payload);
    var state = result.data.status;
    console.log("State result:", state);
    var span = document.createElement('span');
    if (state) {
      span.innerHTML = getSvg();
      span.title = getInfoLabel();
      span.height = '20px';
    }
    return span;
  },
  enabled() {
    return true
  }
});

registerFileAction(inlineAction);
