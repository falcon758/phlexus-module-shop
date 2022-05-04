# Phlexus Shop Module
:package: Phlexus Shop Module

# Setup crons

## Create renewal payments
php /path/to/phlexus/cli.php 'Phlexus\Modules\Shop\Tasks\Subscription' createPayments

## Verify payments
php /path/to/phlexus/cli.php 'Phlexus\Modules\Shop\Tasks\Subscription' verifyPayments

## Verify subscriptions
php /path/to/phlexus/cli.php 'Phlexus\Modules\Shop\Tasks\Subscription' verifySubscription