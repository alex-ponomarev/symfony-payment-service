<?php

namespace App\Exception;

interface ClientVisibleExceptionInterface
{
    public function errorCode(): ApiErrorCode;

    public function publicMessage(): string;
}
