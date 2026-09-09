<?php

namespace App\Exception;

enum ApiErrorCode: string
{
    case InvalidRequest = 'invalid_request';
    case InvalidJson = 'invalid_json';
    case UnsupportedContentType = 'unsupported_content_type';
    case InvalidProduct = 'invalid_product';
    case InvalidTaxNumber = 'invalid_tax_number';
    case InvalidCouponCode = 'invalid_coupon_code';
    case InvalidPaymentProcessor = 'invalid_payment_processor';
    case UnsupportedPaymentProcessor = 'unsupported_payment_processor';
    case ProductNotFound = 'product_not_found';
    case CouponNotFound = 'coupon_not_found';
    case CouponNotActive = 'coupon_not_active';
    case PaymentFailed = 'payment_failed';
    case InternalError = 'internal_error';
}
