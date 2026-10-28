<?php

namespace App\Services;

use RuntimeException;

/**
 * A time window of a station holds more data than is safe to load at once
 * (a station uploading weeks of buffered records in one go): the caller asks
 * again for a shorter window.
 */
class LicorWindowTooLarge extends RuntimeException
{
}
