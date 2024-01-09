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
window.OCA.WorkflowEngine.registerCheck({
    class: 'OCA\\mfazones\\Check\\MfaVerified',
    name: t(appId, 'multi-factor authentication'),
    operators: [
        { operator: 'is', name: t(appId, 'is verified') },
        { operator: '!is', name: t(appId, 'is not verified') },
    ],
    component: MfaVerifiedValue,
},)