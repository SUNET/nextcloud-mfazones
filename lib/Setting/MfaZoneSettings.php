<?php

declare(strict_types=1);

namespace OCA\MfaZone\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class MfaZoneSettings implements ISettings {
    public function getForm(): TemplateResponse {
        return new TemplateResponse('mfaverifiedzone', 'mfazone');
    }

    public function getSection(): string {
        return 'security';
    }

    public function getPriority(): int {
        return 50;
    }
}