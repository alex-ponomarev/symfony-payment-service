**English** | [Русский](README.ru.md)

# Payment Service

A Symfony REST API for calculating product prices and processing payments via
PayPal or Stripe.

The service stores products and coupons in PostgreSQL, validates incoming data
with Symfony Validator, applies discounts, calculates tax based on the tax
number, and passes the final amount to the selected payment processor.

## Features

* final price calculation with or without a coupon;
* percentage-based and fixed discounts;
* coupon validity period validation;
* tax calculation for Germany, Italy, Greece, and France;
* payment processing via PayPal and Stripe;
* support for adding new payment processors through a common interface.

## Calculation rules

All monetary values are stored, calculated, and returned as integer amounts in
cents. For example, the value `11900` corresponds to 119 euros.

The calculation is performed in the following order:

1. The product is loaded.
2. If a coupon is provided, the discount is subtracted from the price.
3. The price cannot become negative.
4. Tax is added to the resulting amount.
5. The result is rounded to an integer number of cents.

Both percentage-based and fixed discounts are supported. Coupon codes are
case-insensitive. A coupon is applied only within the validity period defined by
the `startedAt` and `finishAt` fields.

### Tax numbers

| Country | Format                                  | Rate |
| ------- | --------------------------------------- | ---: |
| Germany | `DE` followed by 9 digits               |  19% |
| Italy   | `IT` followed by 11 digits              |  22% |
| Greece  | `GR` followed by 9 digits               |  24% |
| France  | `FR`, 2 uppercase letters, and 9 digits |  20% |

## REST API

Local application URL: `http://127.0.0.1:8337`.

Business endpoint responses use a common format:

```json
{
    "status": "ok",
    "data": {}
}
```

The `status` field can be either `ok` or `error`. For successful responses,
`data` contains the operation result. For errors, it contains an object with a
stable symbolic error code in the `code` field.

### Price calculation

`POST /calculate-price`

| Field        | Type    | Required | Description                        |
| ------------ | ------- | -------- | ---------------------------------- |
| `product`    | integer | yes      | Positive product identifier        |
| `taxNumber`  | string  | yes      | Tax number for a supported country |
| `couponCode` | string  | no       | Coupon code                        |

Example request:

```json
{
    "product": 1,
    "taxNumber": "GR123456789",
    "couponCode": "D15"
}
```

Successful response:

```json
{
    "status": "ok",
    "data": {
        "price": 10540
    }
}
```

### Purchase

`POST /purchase`

The request contains the same fields as the price calculation endpoint, plus
the required `paymentProcessor` field with either `paypal` or `stripe` as its
value.

Example request:

```json
{
    "product": 1,
    "taxNumber": "IT12345678900",
    "couponCode": "D15",
    "paymentProcessor": "paypal"
}
```

Successful response:

```json
{
    "status": "ok",
    "data": {
        "success": true
    }
}
```

### Errors

`400 Bad Request` is returned for malformed JSON or an invalid Content-Type,
validation errors, business logic errors, and payment processor errors.

The response does not expose internal exception messages or stack traces.
Instead, `data.code` contains a symbolic error code. For example:

```json
{
    "status": "error",
    "data": {
        "code": "coupon_not_found"
    }
}
```

Main error codes:

| Code                            | Reason                                        |
| ------------------------------- | --------------------------------------------- |
| `invalid_request`               | Malformed JSON or field validation error      |
| `product_not_found`             | Product not found                             |
| `coupon_not_found`              | Coupon not found                              |
| `coupon_not_active`             | Coupon is not active                          |
| `payment_failed`                | Payment processor rejected the payment        |
| `unsupported_payment_processor` | Requested payment processor is not supported  |
| `internal_error`                | Unexpected error while processing the request |

Ready-to-use successful and error request examples are available in
[`requests.http`](requests.http) and can be executed directly from PhpStorm.

## Payment processors

Integration with the classes from `systemeio/test-for-candidates` is isolated
behind adapters.

PayPal receives the amount in cents, while Stripe receives the amount converted
to euros.

To add a new processor, it is enough to implement
`PaymentProcessorInterface`. The implementation automatically receives the
`app.payment_processor` DI tag and becomes available through
`PaymentProcessorRegistry`.

## Test data

After loading fixtures into an empty database, the following products are
available:

| ID | Product    | Price in cents |
| -: | ---------- | -------------: |
|  1 | Iphone     |          10000 |
|  2 | Headphones |           2000 |
|  3 | Phone case |           1000 |

Available coupons:

| Code   | Type       |  Discount |
| ------ | ---------- | --------: |
| `D15`  | percentage |       15% |
| `P10`  | percentage |       10% |
| `P100` | percentage |      100% |
| `F5`   | fixed      | 500 cents |

## Technologies

* PHP 8.3;
* Symfony 6.4;
* Doctrine ORM and Doctrine Migrations;
* PostgreSQL 16;
* Docker Compose;
* PHPUnit 12.

## Running the project

Docker, Docker Compose, and Make are required.

Initial project setup:

```bash
make init
```

The command builds the containers, installs Composer dependencies, starts
PostgreSQL, applies migrations, and loads fixtures.

The application starts in the `prod` environment without debug output. Once the
setup is complete, the API will be available at:

`http://127.0.0.1:8337`

To run the application locally in the `dev` environment:

```bash
PAYMENT_APP_ENV=dev make up
```

Main commands:

| Command         | Description                                            |
| --------------- | ------------------------------------------------------ |
| `make up`       | Create and start containers                            |
| `make stop`     | Stop containers                                        |
| `make restart`  | Restart containers without deleting data               |
| `make down`     | Remove containers without deleting volumes             |
| `make reset`    | Remove containers and volumes together with their data |
| `make migrate`  | Apply migrations                                       |
| `make fixtures` | Add missing fixtures                                   |
| `make console`  | Open a shell inside the application container          |

### PostgreSQL connection

| Parameter | Value          |
| --------- | -------------- |
| Host      | `127.0.0.1`    |
| Port      | `5432`         |
| Database  | `payment`      |
| User      | `payment`      |
| Password  | `temppassword` |

### Running tests

```bash
make test
```

The tests use a separate `payment_test` database and do not modify data in the
main `payment` database.

## Developer

**Alexander Ponomarev**
Email: [katapteros@gmail.com](mailto:katapteros@gmail.com)
