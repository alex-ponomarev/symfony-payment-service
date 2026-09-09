<?php

namespace App\Tests\Repository;

use App\Entity\Coupon;
use App\Repository\CouponRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CouponRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CouponRepository $couponRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->couponRepository = self::getContainer()->get(CouponRepository::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        parent::tearDown();
    }

    public function testFindsCouponRegardlessOfRequestedCodeCase(): void
    {
        $coupon = $this->couponRepository->findOneByCode('d15');

        self::assertInstanceOf(Coupon::class, $coupon);
        self::assertSame('D15', $coupon->getCode());
    }

    public function testReturnsNullForUnknownCode(): void
    {
        self::assertNull($this->couponRepository->findOneByCode('UNKNOWN'));
    }

    public function testDatabaseRejectsDuplicateCouponCode(): void
    {
        $this->entityManager->persist(new Coupon(
            'D15',
            Coupon::TYPE_PERCENT,
            15,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+1 day'),
        ));

        $this->expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }
}
