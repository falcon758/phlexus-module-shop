<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;

/**
 * Class AddressType
 *
 * @package Phlexus\Modules\Shop\Models
 */
class AddressType extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const BILLING = 1;

    public const SHIPPING = 2; 

    /**
     * @var int|null
     */
    public ?int $id;

    /**
     * @var string
     */
    public string $addressType;

    /**
     * @var int|null
     */
    public ?int $active;

    /**
     * @var string|null
     */
    public ?string $createdAt;

    /**
     * @var string|null
     */
    public ?string $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('address_type');
    }
}
