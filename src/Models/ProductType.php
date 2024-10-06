<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;

/**
 * Class ProductType
 *
 * @package Phlexus\Modules\Shop\Models
 */
class ProductType extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const PHYSICAL = 'physical';

    public const VIRTUAL = 'virtual';

    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var int|null
     */
    public ?int $active = null;

    /**
     * @var string|null
     */
    public ?string $createdAt = null;

    /**
     * @var string|null
     */
    public ?string $modifiedAt = null;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('product_type');
    }
}
