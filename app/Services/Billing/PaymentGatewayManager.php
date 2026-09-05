<?php

namespace App\Services\Billing;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Setting;
use App\Services\Billing\Gateways\ManualTransferGateway;
use App\Services\Billing\Gateways\MidtransGateway;
use App\Services\Billing\Gateways\TripayGateway;
use App\Services\Billing\Gateways\XenditGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $drivers = [];

    public function __construct()
    {
        $this->drivers = [
            'manual' => new ManualTransferGateway(),
            'midtrans' => new MidtransGateway(),
            'xendit' => new XenditGateway(),
            'tripay' => new TripayGateway(),
        ];
    }

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: (string) Setting::get('billing_payment_gateway', 'manual');

        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Payment gateway driver '{$name}' is not supported.");
        }

        return $this->drivers[$name];
    }

    public function getAvailableDrivers(): array
    {
        return array_keys($this->drivers);
    }
}
