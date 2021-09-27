<?php

declare(strict_types=1);

namespace StockOff\Providers;

use Phlexus\Providers\AbstractProvider;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentStripeProvider extends AbstractProvider
{
    /**
     * Provider name
     *
     * @var string
     */
    protected $providerName = 'paymentStripe';

    /**
     * @param array $parameters
     */
    public function register(array $parameters = []): void
    {
        $apiKey = (string)getenv('STRIPE_SECRET');
        $this->di->setShared('stripeCheckout', function () use ($apiKey) {
            Stripe::setApiKey($apiKey);

            return new Session();
        });
    }
}
