<?php

namespace App\Services\Http;

/** Which token to attach to an outbound request. */
enum TokenType
{
    case User;
    case Service;
}