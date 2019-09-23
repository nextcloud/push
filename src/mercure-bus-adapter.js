/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import {getCurrentUser} from 'nextcloud-auth'
import {emit} from 'nextcloud-event-bus'
import {loadState} from 'nextcloud-initial-state'

const broadcastMercureEvents = uid => {
	let url;
	try {
		url = new URL(loadState('push', 'mercure_url'))
	} catch (e) {
		console.error('No Mercure URL set, can\'t open event source', e)
		return
	}

	url.searchParams.append('topic', uid)
	const source = new EventSource(url)

	source.onmessage = e => {
		const data = JSON.parse(e.data)

		if (data === undefined || data.payload === undefined) {
			console.warn('Ignoring event with invalid payload')
			return
		} if (data.payload.name === undefined) {
			console.warn('Ignoring event without name', e)
			return
		}

		console.debug('received ' + data.payload.name + ' event from the server')

		emit(data.payload)
	}
}

// Only connect to Mercure for logged in users
const user = getCurrentUser();
if (user !== null) {
	broadcastMercureEvents(user.uid)
}
