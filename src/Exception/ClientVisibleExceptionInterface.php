<?php

namespace App\Exception;

interface ClientVisibleExceptionInterface
{
    public function errorCode(): string;
}
