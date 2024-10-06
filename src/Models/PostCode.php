<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;

/**
 * Class PostCode
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PostCode extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var int|null
     */
    public ?int $id;

    /**
     * @var string
     */
    public string $postCode;

    /**
     * @var int|null
     */
    public ?int $active;

    /**
     * @var int
     */
    public int $localeID;

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
        $this->setSource('post_codes');

        $this->hasOne('localeID', Locale::class, 'id', [
            'alias'    => 'locale',
            'reusable' => true,
        ]);
    }

    /**
     * Create post code or return if exists
     * 
     * @param string $postcode  Post code to create
     * @param int    $localeID  Locale id to assign
     *
     * @return PostCode
     */
    public static function createPostCode(string $postcode, int $localeID): PostCode {
        $postcode = trim($postcode);

        $newPostcode = self::findFirst([
            'conditions' => 'active = :active: AND localeID = :locale_id: AND postCode = :postCode:',
            'bind'       => [
                'active'    => self::ENABLED,
                'locale_id' => $localeID,
                'postCode'  => $postcode,
            ],
        ]);

        if ($newPostcode) {
            return $newPostcode;
        }

        $newPostcode           = new self;
        $newPostcode->postCode = $postcode;
        $newPostcode->localeID = $localeID;

        if (preg_match('/^[0-9\-]+$/', $postcode) !== 1 || !$newPostcode->save()) {
            throw new \Exception('Unable to process post code');
        }

        return $newPostcode;
    }
}
