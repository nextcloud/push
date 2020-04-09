<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 *
 */

namespace OCA\Push\Helper;

class JWT {

	/**
	 * @param string[] $subscribe list of authorized targets to subscribe to
	 * @param string[] $publish list of authorized targets to publish to
	 * @param string $secret the secret
	 * @return string The signature
	 */
	public static function generateJWT(array $subscribe, array $publish, string $secret): string {
		$header = [
			'alg' => 'HS256',
			'typ' => 'JWT',
		];

		$data = [
			'mercure' => [
				'subscribe' => $subscribe,
				'publish' => $publish,
			],
		];

		$headerB64 = self::base64urlencode(json_encode($header));
		$dataB64 = self::base64urlencode(json_encode($data));

		$sig = hash_hmac('sha256', $headerB64 . '.' . $dataB64, $secret, true);
		$sigB64 = self::base64urlencode($sig);

		return $headerB64 . '.' . $dataB64 . '.' . $sigB64;

	}

	private static function base64urlencode(string $data): string {
		// First of all you should encode $data to Base64 string
		$b64 = base64_encode($data);

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
		$url = strtr($b64, '+/', '-_');

		// Remove padding character from the end of line and return the Base64URL result
		return rtrim($url, '=');
	}
}
