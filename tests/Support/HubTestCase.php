<?php

namespace Modules\Sviat\DeliveryFilter\Support;

require_once __DIR__ . '/RequestStub.php';
require_once __DIR__ . '/FakeCarriers.php';

use Okay\Core\Design;
use Okay\Core\Modules\Extender\ChainExtender;
use Okay\Core\Modules\Modules;
use Okay\Modules\Sviat\DeliveryFilter\Extenders\BackendExtender;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\DeliveryFilterHelper;
use Okay\Modules\Sviat\DeliveryFilter\Helpers\Redirector;
use PHPUnit\Framework\TestCase;

/**
 * Хаб із фейковими перевізниками, зареєстрованими так само, як це робить Init перевізника.
 * Ланки ChainExtender статичні, тож кожен тест прибирає свої тригери.
 */
abstract class HubTestCase extends TestCase
{
    /** @var array<string, mixed> */
    protected array $assigned = [];
    /** @var list<string> */
    protected array $redirects = [];
    protected bool $hubEnabled = true;

    private const array TRIGGERS = ['findDeliveryStatusFilters', 'filterByDeliveryStatus', 'findOrderIdsByShipmentNumber'];

    protected function tearDown(): void
    {
        $triggers = array_map(static fn(string $method): string => DeliveryFilterHelper::class . '::' . $method, self::TRIGGERS);
        \Closure::bind(static function () use ($triggers): void {
            foreach ($triggers as $trigger) {
                unset(ChainExtender::$triggers[$trigger]);
            }
        }, null, ChainExtender::class)();
        FakeCarrierA::reset();
        FakeCarrierB::reset();
        unset($_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    /** @param class-string $carrier */
    protected function connect(string $carrier): void
    {
        $chain = new ChainExtender();
        $chain->newExtension(DeliveryFilterHelper::class, 'findDeliveryStatusFilters', $carrier, 'groups');
        $chain->newExtension(DeliveryFilterHelper::class, 'filterByDeliveryStatus', $carrier, 'filter');
        $chain->newExtension(DeliveryFilterHelper::class, 'findOrderIdsByShipmentNumber', $carrier, 'search');
    }

    /** @param array<string, mixed> $get */
    protected function extender(array $get, string $method = 'GET'): BackendExtender
    {
        $_SERVER['REQUEST_URI'] = '/backend/index.php?' . http_build_query($get);
        $design = $this->createStub(Design::class);
        $design->method('assign')->willReturnCallback(function ($name, $value = null) use ($design) {
            $this->assigned[$name] = $value;

            return $design;
        });
        $redirector = new class($this->redirects) extends Redirector {
            /** @param list<string> $log */
            public function __construct(private array &$log)
            {
            }

            public function to(string $url): void
            {
                $this->log[] = $url;
            }
        };

        $modules = $this->createStub(Modules::class);
        $modules->method('isActiveModule')->willReturnCallback(fn($vendor, $name): bool => $vendor === 'Sviat' && $name === 'DeliveryFilter' && $this->hubEnabled);

        return new BackendExtender(new DeliveryFilterHelper(), new RequestStub([], $get, $method), $design, $redirector, $modules);
    }
}
