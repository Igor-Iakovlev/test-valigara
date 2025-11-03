<?php

declare(strict_types=1);

namespace tests;

use App\Data\FileBuyer;
use App\Data\FileOrder;
use App\FbaShippingService;
use App\ClientBuilder\MockClientBuilder;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FbaShippingServiceTest extends TestCase
{
    use NormalizerAwareTrait;

    private const SCENARIO_SUCCESS = 'success';
    private const SCENARIO_NO_TRACKING = 'no_tracking';
    private const SCENARIO_PARTIAL_ERROR = 'partial_error';
    private const SCENARIO_ERROR_STATUS = 'error_status';

    private const DEFAULT_SELLER_ID = 'MCF-16400';
    private const DEFAULT_TRACKING_NUM = '1Z999AA1234567890';

    private MockClientBuilder $mockClientBuilder;
    private Logger $logger;
    private ValidatorInterface $validator;
    private RateLimiterFactory $rateLimiterFactory;
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->mockClientBuilder = new MockClientBuilder();
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->rateLimiterFactory = new RateLimiterFactory(
            [
                'id' => 'rate_limit',
                'policy' => 'token_bucket',
                'limit' => 30,
                'rate' => [
                    'interval' => '1 second',
                    'amount' => 2,
                ],
            ],
            new InMemoryStorage(),
            new LockFactory(
                new InMemoryStore(),
            ),
        );
        $this->setNormalizer(
            new Serializer(
                [
                    new BackedEnumNormalizer(),
                    new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s']),
                    new ObjectNormalizer(
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        [
                            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                        ]
                    ),
                ],
                [new JsonEncoder()],
            )
        );
        $this->logger = $this->createMock(Logger::class);
    }

    #[DataProvider('shipProvider')]
    public function testShip(
        string $scenario,
        int $orderId,
        int $buyerId,
        bool $expectLogWarning = false,
        ?string $expectedExceptionClass = null,
    ): void {
        $client = match ($scenario) {
            self::SCENARIO_SUCCESS => (new MockClientBuilder())
                ->withSuccess(self::DEFAULT_SELLER_ID, self::DEFAULT_TRACKING_NUM)->build(),
            self::SCENARIO_NO_TRACKING => (new MockClientBuilder())
                ->withoutTracking(self::DEFAULT_SELLER_ID)->build(),
            self::SCENARIO_PARTIAL_ERROR => (new MockClientBuilder())
                ->withPartialError(self::DEFAULT_SELLER_ID, self::DEFAULT_TRACKING_NUM)->build(),
            self::SCENARIO_ERROR_STATUS => (new MockClientBuilder())
                ->withErrorStatus(self::DEFAULT_SELLER_ID)->build(),
        };

        $order = new FileOrder($orderId);
        $buyer = (new FileBuyer())->load($buyerId);

        $service = new FbaShippingService(
            $client,
            $this->validator,
            $this->rateLimiterFactory,
            $this->normalizer,
            $this->logger
        );

        if ($expectLogWarning) {
            $this->logger->expects($this->once())->method('warning')->with($this->stringContains('Partial FBA errors'));
        } else {
            $this->logger->expects($this->never())->method($this->anything());
        }
        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $service->ship($order, $buyer);
            return;
        }
        $trackingNum = $service->ship($order, $buyer);
        $this->assertSame(self::DEFAULT_TRACKING_NUM, $trackingNum);
    }

    public static function shipProvider()
    {
        return [
            [self::SCENARIO_NO_TRACKING, 16400, 29664, false, RuntimeException::class],
            [self::SCENARIO_PARTIAL_ERROR, 16400, 29664, true, null],
            [self::SCENARIO_ERROR_STATUS, 16400, 29664, false, ClientException::class],
            [self::SCENARIO_SUCCESS, 16404, 29664, false, InvalidArgumentException::class],
            [self::SCENARIO_SUCCESS, 16400, 29664, false, null],
        ];
    }
}
