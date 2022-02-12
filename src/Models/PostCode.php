<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class PostCode
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PostCode extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

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
        $postcode = trim($postcode);

        $newPostcode = self::findFirst([
            'conditions' => 'active = :active: AND localeID = :locale_id: AND post_code = :post_code:',
            'bind'       => [
                'active'    => self::ENABLED,
                'locale_id' => $localeId,
                'post_code' => $postcode,
            ],
        ]);

        if ($newPostcode) {
            return $newPostcode;
        }

        $newPostcode = new self;
        $newPostcode->post_code = $postcode;
        $newPostcode->localeID = $localeId;

        if (preg_match('/^[0-9]+-[0-9]+$/', $post_code) !== 1 || !$newPostcode->save()) {
            throw new \Exception('Unable to process post code');
        }

        return $newPostcode;
    }
}
