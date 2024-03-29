/**
 * @copyright Copyright (c) 2024 Michiel de Jong <michiel@unhosted.org>
 * @copyright Copyright (c) 2024 Micke Nordin <kano@sunet.se>
 *
 * @author Michiel de Jong <michiel@unhosted.org>
 * @author 2024 Micke Nordin <kano@sunet.se>
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
import Vue from 'vue'
import MFATabEmpty from './views/MFATabEmpty.vue'
import MFATab from './views/MFATab.vue'
import { getRequestToken } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'

Vue.prototype.t = t
const appId = 'mfazones'

const View = Vue.extend(MFATab)
let MFATabInstance = null

if ((typeof window.OCA !== 'undefined') && typeof window.OCA.WorkflowEngine !== 'undefined') {
  window.OCA.WorkflowEngine.registerCheck({
    class: 'OCA\\mfazones\\Check\\MfaVerified',
    name: t(appId, 'multi-factor authentication'),
    operators: [
      { operator: 'is', name: t(appId, 'is verified') },
      { operator: '!is', name: t(appId, 'is not verified') },
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
})
