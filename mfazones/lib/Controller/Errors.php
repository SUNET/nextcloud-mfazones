<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\mfazones\Controller;

use Closure;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\mfazones\Service\NotFoundException;

trait Errors
{
  protected function handleNotFound(Closure $callback): DataResponse
  {
    try {
      return new DataResponse($callback());
    } catch (NotFoundException $e) {
      $message = ['message' => $e->getMessage()];
      return new DataResponse($message, Http::STATUS_NOT_FOUND);
    }
  }
}
