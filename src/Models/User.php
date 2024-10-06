<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Modules\BaseUser\Models\User as  UserModel;

/**
 * Class User
 *
 * @package Phlexus\Modules\Shop\Models
 */
class User extends UserModel
{
    /**
     * @var string|null
     */
    public ?string $name = null;

    /**
     * @var string|null
     */
    public ?string $taxNumber = null;

    /**
     * Get encrypt fields
     *
     * @return array Fields
     */
    public static function getEncryptFields() : array
    {
        //@ToDo: Check encryption issue
        return array_merge(
            parent::getEncryptFields(),
            [
                //'name',
                //'taxNumber'
            ]
        );
    }

    /**
     * After Fetch
     *
     * @return void
     */
    public function afterFetch()
    {
        parent::afterFetch();
    }

    /**
     * Before Save
     *
     * @return void
     */
    public function beforeSave()
    {
        parent::beforeSave();
    }

    public function savePersonalInfo($name, $taxNumber): bool
    {
        $this->name      = self::encrypt($name);
        $this->taxNumber = self::encrypt($taxNumber);

        return $this->save();
    }
}
