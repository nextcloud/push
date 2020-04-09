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

import Axios from '@nextcloud/axios'
import {emit} from '@nextcloud/event-bus'
import {generateUrl} from '@nextcloud/router'
import {getCurrentUser} from '@nextcloud/auth'
import {loadState} from '@nextcloud/initial-state'
import { EventSourcePolyfill } from 'event-source-polyfill';

import logger from './logger'

const init = uid => {
	let config
	try {
		config = loadState('push', 'config')
	} catch (error) {
		logger.error('No Mercure config set', {error})
		return
	}

	switch (config.gateway) {
		case 'mercure':
			logger.debug('using Mercure as SSE source')
			broadcastMercureEvents(uid, config.hubUrl, config.jwt)
			break;
		case 'poll':
			logger.debug('using the poll endpoint as SSE source')
			broadcastPollEvents(config.now)
			break;
		default:
			logger.error('invalid push gateway ' + config.gateway)
	}
}

const processSse = data => {
	if (data.name === undefined) {
		logger.warn('Ignoring event without name', {data})
		return
	}

	logger.debug('received ' + data.name + ' event from the server', {data})

	emit(data)
}

const broadcastMercureEvents = (uid, hubUrl, jwt) => {
	const url = new URL(hubUrl + '/.well-known/mercure')
	url.searchParams.append('topic', 'users/' + uid)
	const source = new EventSourcePolyfill(url, {
		headers: {
			'Authorization': 'Bearer ' + jwt,
		}
	})

	source.onmessage = e => processSse(JSON.parse(e.data))
}

const broadcastPollEvents = offset => {
	setTimeout(() => {
		const url = generateUrl('/apps/push/poll?cursor={cursor}', {
			cursor: offset,
		})

		Axios.get(url)
			.then(resp => resp.data)
			.then(events => {
				if (events.constructor !== Array) {
					logger.error('polled events are not an array')
					return
				}

				events.forEach(processSse)

				if (events.length === 0) {
					// Nothing new -> reuse the offset
					broadcastPollEvents(offset)
				} else {
					// Last event determines new offset
					broadcastPollEvents(events[events.length - 1].createdAt)
				}
			})
			.catch(error => {
				logger.error('polling failed', {error})

				// Retry from previous offset to hopefully catch all events
				broadcastPollEvents(offset)
			})
	}, 10 * 1000)


}

// Only connect to Mercure for logged in users
const user = getCurrentUser();
if (user !== null) {
	init(user.uid)
}
