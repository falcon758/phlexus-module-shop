<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class Product
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Product extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var int
     */
    public int $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var int
     */
    public int $active;

    /**
     * @var string
     */
    public string $createdAt;

    /**
     * @var string
     */
    public string $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('products');
    }
}
