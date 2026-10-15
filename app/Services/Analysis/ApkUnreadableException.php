<?php

namespace App\Services\Analysis;

use RuntimeException;

/**
 * File bukan APK yang bisa dibaca (bukan ZIP, tanpa manifest, atau manifest rusak).
 */
class ApkUnreadableException extends RuntimeException {}
