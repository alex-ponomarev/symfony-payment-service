<?php

namespace App\DataFixtures;

use App\Entity\Coupon;
use App\Entity\Product;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    private const PRODUCTS = [
        'Iphone' => 10000,
        'Наушники' => 2000,
        'Чехол' => 1000,
    ];

    private const COUPONS = [
        'D15' => [Coupon::TYPE_PERCENT, 15],
        'P10' => [Coupon::TYPE_PERCENT, 10],
        'P100' => [Coupon::TYPE_PERCENT, 100],
        'F5' => [Coupon::TYPE_FIXED, 500],
    ];

    public function load(ObjectManager $manager): void
    {
        $this->loadProducts($manager);
        $this->loadCoupons($manager);

        $manager->flush();
    }

    private function loadProducts(ObjectManager $manager): void
    {
        $products = $manager->getRepository(Product::class)->findBy([
            'name' => array_keys(self::PRODUCTS),
        ]);
        $existingNames = array_fill_keys(
            array_map(static fn (Product $product): string => $product->getName(), $products),
            true,
        );

        foreach (self::PRODUCTS as $name => $priceInCents) {
            if (!isset($existingNames[$name])) {
                $manager->persist(new Product($name, $priceInCents));
            }
        }
    }

    private function loadCoupons(ObjectManager $manager): void
    {
        $coupons = $manager->getRepository(Coupon::class)->findBy([
            'code' => array_keys(self::COUPONS),
        ]);
        $existingCodes = array_fill_keys(
            array_map(static fn (Coupon $coupon): string => $coupon->getCode(), $coupons),
            true,
        );
        $startedAt = new DateTimeImmutable('2020-01-01');
        $finishAt = new DateTimeImmutable('2100-01-01');

        foreach (self::COUPONS as $code => [$type, $discountValue]) {
            if (!isset($existingCodes[$code])) {
                $manager->persist(new Coupon(
                    $code,
                    $type,
                    $discountValue,
                    $startedAt,
                    $finishAt,
                ));
            }
        }
    }
}
