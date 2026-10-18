<?php

namespace App\Ai;

use RuntimeException;

/**
 * The model answered, but not with usable JSON (refusal, truncated output, invalid JSON).
 */
class AiResponseException extends RuntimeException {}
