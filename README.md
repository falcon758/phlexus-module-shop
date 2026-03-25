# Phlexus Shop Module
:package: Phlexus Shop Module

# Setup crons

## Create renewal payments
php /path/to/phlexus/cli.php 'Phlexus\Modules\Shop\Tasks\Subscription' createPayments

## Verify payments
php /path/to/phlexus/cli.php 'Phlexus\Modules\Shop\Tasks\Subscription' verifyPayments

## Verify subscriptions
php /path/to/phlexus/cli.php 'Phlexus\Modules\Shop\Tasks\Subscription' verifySubscription

# Payments: Apple Pay & Google Pay

- Configure provider: add `stripe` keys under your global `payments` config:
	- `payments[stripe][secret_key]`: your Stripe secret API key.
- Enable methods: create `PaymentMethod` records in DB with IDs matching:
	- Apple Pay: 3
	- Google Pay: 4
- Routes:
	- Success callbacks are handled at `/payment/callback/stripe/{paymentHash}` and `/payment/callback/stripe/{paymentHash}`.
- How it works:
	- Start payment creates a Stripe Checkout Session and redirects the user.
	- Wallets (Apple Pay, Google Pay) are automatically available on Checkout.
	- On successful payment, the module marks the `Payment` and `Order` as paid.
- Requirements:
	- Stripe account with Apple/Google Pay enabled for your domain.
	- Domain verification with Apple Pay (via Stripe) as needed.