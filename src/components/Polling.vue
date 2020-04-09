<!--
  - Push - Nextcloud Push Service
  -
  - This file is licensed under the Affero General Public License version 3 or
  - later. See the COPYING file.
  -
  - @author Maxence Lange <maxence@artificial-owl.com>
  - @copyright 2019
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<style scoped>
</style>

<script>
import axios from '@nextcloud/axios'

export default {
	name: 'Polling',
	components: {},
	data: function() {
		return {
			polling: {
				items: [],
				lastEventId: -1,
				meta: {}
			},
			meta: {
				debug: false,
				polling: 'short',
				delay: 5
			},
			callbacks: [],
			divAppMenu: document.getElementById('appmenu')
		}
	},

	beforeMount: function() {
		window.OCA.Push = {
			isEnabled: () => {
				return true
			},
			test: (userId) => {
				this.test(userId)
			},

			addCallback: (callback, app, source) => {
				this.addCallback(callback, app, source)
			}
		}
	},

	created: function() {
		this.divAppMenu = document.getElementById('appmenu')
		// eslint-disable-next-line no-console
		console.log('Nextcloud Push available')

		this.pollingUp()
	},

	methods: {
		pollingUp: function() {
			this.log('init polling request - ' + this.polling.lastEventId)
			axios.get(OC.generateUrl('/apps/push/polling/' + this.polling.lastEventId)).then((response) => {
				this.manageMeta(response.data)
				this.log('response: ' + JSON.stringify(response.data))
				if (response.data.status !== 1) {
					setTimeout(() => {
						this.pollingUp()
					}, this.meta.delay * 1000)

					return
				}

				this.polling = response.data
				this.manageCurrentItems()

				let delay = (this.meta.polling === 'short') ? this.meta.delay : 0
				setTimeout(() => {
					this.pollingUp()
				}, delay * 1000)
			}).catch(() => {
				setTimeout(() => {
					this.pollingUp()
				}, this.meta.delay * 1000)
			})
		},

		getCurrentApp: function() {
			let allApps = this.divAppMenu.children
			for (let i = 0; i < allApps.length; i++) {
				let item = allApps[i]
				if (this.isActiveMenuItem(item)) {
					return item.getAttribute('data-id')
				}
			}

			return ''
		},

		isActiveMenuItem(item) {
			for (let i = 0; i < item.children.length; i++) {
				let sub = item.children[i]
				if (sub.className === 'active') {
					return true
				}
			}

			return false
		},

		manageMeta: function(data) {
			if (!data.meta) {
				return
			}

			this.meta.debug = (data.meta.debug) ? data.meta.debug : this.meta.debug
			this.meta.polling = (data.meta.debug) ? data.meta.polling : this.meta.polling
			this.meta.delay = (data.meta.delay) ? data.meta.delay : this.meta.delay
		},

		manageCurrentItems: function() {
			let self = this
			let items = this.polling.items
			let currentApp = this.getCurrentApp()

			this.log('current app: ' + currentApp)
			items.forEach(function(item) {
				self.log('new item: ' + JSON.stringify(item))
				let limitTo = (item.meta && item.meta.limitedToApps) ? item.meta.limitedToApps : []
				let filtered = (item.meta && item.meta.filteredApps) ? item.meta.filteredApps : []

				if (currentApp !== '') {
					if (filtered.includes(currentApp)) {
						self.log('current app (' + currentApp + ') is filtered: ' + JSON.stringify(filtered))
						return
					}
					if (limitTo.length > 0 && !limitTo.includes(currentApp)) {
						self.log('current app is ' + currentApp + ' but item is limited to ' + JSON.stringify(limitTo))
						return
					}
				}

				self.toCallbacks(item)

				if (item.type === 'Callback') {
					self.log('callback only: ' + JSON.stringify(item))
					return
				}

				if (item.type === 'Notification') {
					return self.pushNotification(item)
				}

				if (item.type === 'Event') {
					return self.broadcastEvent(item)
				}
			})

			this.polling.items = []
		},

		toCallbacks: function(item) {
			this.callbacks.forEach(function(callback) {
				if (callback.app !== '' && callback.app !== item.app) {
					return
				}
				if (callback.source !== '' && item.source !== undefined && callback.source !== item.source) {
					return
				}

				let fn = callback.callback
				fn(item)
			})
		},

		pushNotification: function(item) {
			// let title = item.source
			let message = item.payload.message
			let level = item.payload.level
			let options = {}
			// let link = ''

			this.log('pushing notification: ' + JSON.stringify(item))
			if (level === 'success') {
				OCP.Toast.success(message, options)
				return
			}
			if (level === 'info') {
				OCP.Toast.info(message, options)
				return
			}
			if (level === 'warning') {
				OCP.Toast.warning(message, options)
				return
			}
			if (level === 'error') {
				OCP.Toast.error(message, options)
				return
			}

			OCP.Toast.message(message, options)
		},

		broadcastEvent: function(item) {
			let command = item.source
			let payload = item.payload
			// eslint-disable-next-line no-undef
			this.log('broadcasting event: ' + JSON.stringify(item))
			this.executeFunction(command, payload)
		},

		executeFunction: function(functionName, payload) {
			let context = window
			let namespaces = functionName.split('.')
			let func = namespaces.pop()
			for (let i = 0; i < namespaces.length; i++) {
				if (context[namespaces[i]] === undefined) {
					// eslint-disable-next-line no-console
					console.log('Nextcloud Push: Unknown function \'' + functionName + '\'')
					return
				}
				context = context[namespaces[i]]
			}

			let fn = context[func]
			if (typeof fn === 'function') {
				fn(payload)
			}
		},

		addCallback: function(callback, app, source) {
			this.log('new callback: ' + typeof callback + ' - app: ' + app + ' - source: ' + source)
			if (typeof callback !== 'function') {
				return
			}

			this.callbacks.push({
				callback: callback,
				app: (app === undefined) ? '' : app,
				source: (source === undefined) ? '' : source
			})
		},

		test: function(data) {
			if (typeof data === 'string') {
				data = { message: data }
			}

			if (data.message === undefined) {
				// eslint-disable-next-line no-console
				console.log('issue with data while testing: ' + JSON.stringify(data))
				return
			}

			OCP.Toast.message(data.message)
		},

		log: function(line) {
			if (!this.meta.debug) {
				return
			}

			// eslint-disable-next-line no-console
			console.log('Nextcloud Push: ' + line)
		}
	}
}

</script>
