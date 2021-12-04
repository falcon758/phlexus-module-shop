<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phlexus\Modules\Shop\Models\Address;
use Phlexus\Modules\Shop\Models\PostCode;
use Phlexus\Modules\Shop\Models\Locale;

/**
 * Class Address
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Address extends Model
{
    const DISABLED = 0;

    const ENABLED = 1;

    public $id;

    public $address;

    public $active;

    public $postCodeID;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('address');

        $this->hasOne('postCodeID', PostCode::class, 'id', [
            'alias'    => 'PostCode',
            'reusable' => true,
        ]);
    }

    /**
     * Create address or return if exists
     * 
     * @param string $address Address to create
     * @param string $postCode Post code to create
     * @param string $locale Locale to create
     * @param int    $country Locale to verify
     *
     * @return Address
     * 
     * @throws Exception
     */
    public static function createAddress(string $address, string $postCode, string $locale, int $country): Address {
        $newLocale = Locale::createLocale($locale, $country);

        $newPostCode = PostCode::createPostCode($postCode, (int) $newLocale->id);
        
        $newAddress = self::findFirst([
            'conditions' => 'active = :active: AND postCodeID = :post_code_id: AND address = :address:',
            'bind'       => [
                'active'    => self::ENABLED,
                'post_code_id' => $newPostCode->id,
                'address' => $address,
            ],
        ]);

        if ($newAddress) {
            return $newAddress;
        }

        $newAddress          = new self;
        $newAddress->address = $address;
        $newAddress->postCodeID = $newPostCode->id;

        if (!$newAddress->save()) {
            throw new \Exception('Unable to process address');
        }
        
        return $newAddress;
    }

    /**
     * Auto create all address chain or return if exists
     * 
     * @param string $address Address to create
     * @param string $postCode Post code to create
     * @param string $locale Locale to create
     * @param int    $country Locale to verify
     *
     * @return Address
     */
    public static function createAddressChain(string $address, string $postCode, string $locale, int $country): Address {
        $orderFlow = [
            'address'  => [
                'class' => Address::class,
                'field' => 'address',
                'related' => [
                    'postCodeID' => 'postCodeId'
                ]
            ],
            'postCode' => [
                'class' => PostCode::class,
                'field' => 'post_code',
                'related' => [
                    'localeID' => 'localeId'
                ]
            ],
            'locale'   => [
                'class' => Locale::class,
                'field' => 'name',
                'related' => [
                    'countryID' => 'countryId'
                ]
            ]
        ];
        
        $orderKeys = array_keys($orderFlow);

        $position = 0;
        $addressId = null;
        $postCodeId = null;
        $localeId = null;
        while (count($orderFlow) > 0) {
            $value   = key($orderFlow);
            $model   = current($orderFlow);
            $class   = $model['class'];
            $field   = $model['field'];
            $related = isset($model['related']) ? $model['related'] : [];
            
            $position = array_search($value, $orderKeys);

            $params = [
                'conditions' => "$field = :$value:",
                'bind'       => [
                    $value => ${$value}
                ],
            ];

            foreach ($related as $dbField => $valField) {
                $params['conditions'] .= " AND $dbField = :$dbField:";
                $params['bind'][$dbField] = ${$valField};
            }

            $record = $class::findFirst($params);

            $isLast = $position === count($orderFlow) - 1;
            if ($record) {
                ${$value . 'Id'} = $record->id;

                if ($isLast) {
                    unset($orderFlow[$value]);
                    reset($orderFlow);
                } else {
                    $prev_position = $position - 1;
                    $orderFlow = array_slice($orderFlow, 0, $prev_position >= 0 ? $prev_position : 0);
                }
            } elseif ($isLast) {
                $newModel = new $class;
                $newModel->field = ${$value};

                foreach ($related as $dbField => $valField) {
                    $newModel->$dbField = ${$valField};
                }
                
                if (!$newModel->save()) {
                    throw new \Exception('Unable to process address');
                } elseif ($newModel instanceof Address) {
                    return $newModel;
                }
            }
        }
    }
}
