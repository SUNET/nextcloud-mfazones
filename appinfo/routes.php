<?php

// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
	'routes' => [
		['name' => 'mfazones#get', 'url' => '/get', 'verb' => 'GET'],
		['name' => 'mfazones#getMfaStatus', 'url' => '/getMfaStatus', 'verb' => 'GET'],
		['name' => 'mfazones#getList', 'url' => '/getList', 'verb' => 'GET'],
		['name' => 'mfazones#access', 'url' => '/access', 'verb' => 'GET'],
		['name' => 'mfazones#set', 'url' => '/set', 'verb' => 'POST'],
	]
];
