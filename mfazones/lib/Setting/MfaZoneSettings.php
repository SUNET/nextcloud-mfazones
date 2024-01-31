<?php

// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace OCA\MfaZone\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class MfaZoneSettings implements ISettings
{
  public function getForm(): TemplateResponse
  {
    return new TemplateResponse('mfazones', 'mfazone');
  }

  public function getSection(): string
  {
    return 'security';
  }

  public function getPriority(): int
  {
    return 50;
  }
}
