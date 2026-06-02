<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2025 Mikael Nordin <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

require_once __DIR__ . '/vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config
    ->getFinder()
    ->ignoreVCSIgnored(true)
    ->notPath('build')
    ->notPath('l10n')
    ->notPath('src')
    ->notPath('vendor')
    ->notPath('node_modules')
    ->in(__DIR__);
return $config;
