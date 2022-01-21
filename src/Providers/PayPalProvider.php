<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Providers;

use Phlexus\Providers\AbstractProvider;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class PayPalProvider extends AbstractProvider
{
    /**
     * Provider name
     *
     * @var string
     */
    protected $providerName = 'paypal';

    /**
     * @param array $parameters
     */
    public function register(array $parameters = []): void
    {
        $configs = Helpers::phlexusConfig($this->providerName)->toArray();

        $this->di->setShared($this->providerName, function () use ($configs) {
            $clientId = $configs['client_id'];
            $clientSecret = $configs['client_secret'];
    
            $environment = new SandboxEnvironment($clientId, $clientSecret);
            $client = new PayPalHttpClient($environment);

            return $client;
        });
    }
}
