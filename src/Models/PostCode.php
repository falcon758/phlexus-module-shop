<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phlexus\Modules\Shop\Models\Locale;

/**
 * Class PostCode
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PostCode extends Model
{
    const DISABLED = 0;

    const ENABLED = 1;

    public $id;

    public $post_code;

    public $active;

    public $localeID;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('post_codes');

        $this->hasOne('localeID', Locale::class, 'id', [
            'alias'    => 'Locale',
            'reusable' => true,
        ]);
    }

    /**
     * Create post code or return if exists
     * 
     * @param string $postcode  Post code to create
     * @param int    $localeId  Locale id to assign
     *
     * @return PostCode
     */
    public static function createPostCode(string $postcode, int $localeId): PostCode {
        $postcode = self::findFirst([
            'conditions' => 'active = :active: AND LocaleID = :locale_id: AND post_code = :post_code:',
            'bind'       => [
                'active'    => self::ENABLED,
                'locale_id' => $localeId,
                'post_code' => $postcode,
            ],
        ]);

        if ($postcode) {
            return $postcode;
        }

        $postcode = new self;
        $postcode->post_code = $postcode;
        $postcode->localeID = $localeId;

        return $postcode->save() ? $postcode : null;
    }
}