<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Providers;

use Phlexus\Providers\AbstractProvider;
use Phlexus\Helpers;
use Stripe\StripeClient;

class StripeProvider extends AbstractProvider
{
    /**
     * Provider name
     *
     * @var string
     */
    protected string $providerName = 'stripe';

    /**
     * Register provider
     *
     * @param array $parameters
     */
    public function register(array $parameters = []): void
    {
        $payments = Helpers::phlexusConfig('payments')->toArray();

        if (!isset($payments[$this->providerName])) {
            return;
        }

        $configs = $payments[$this->providerName];

        $this->di->setShared($this->providerName, function () use ($configs) {
            $secretKey = $configs['secret_key'] ?? '';

            return new StripeClient($secretKey);
        });
    }
}
