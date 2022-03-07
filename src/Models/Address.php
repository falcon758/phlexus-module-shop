<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class Address
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Address extends Model
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
    public string $address;

    /**
     * @var int
     */
    public int $active;

    /**
     * @var int
     */
    public int $postCodeID;

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
        $this->setSource('address');

        $this->hasOne('postCodeID', PostCode::class, 'id', [
            'alias'    => 'postCode',
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

        $address = trim($address);

        $newAddress = self::findFirst([
            'conditions' => 'active = :active: AND postCodeID = :postCodeID: AND address = :address:',
            'bind'       => [
                'active'    => self::ENABLED,
                'postCodeID' => $newPostCode->id,
                'address' => $address,
            ],
        ]);

        if ($newAddress) {
            return $newAddress;
        }

        $newAddress          = new self;
        $newAddress->address = $address;
        $newAddress->postCodeID = $newPostCode->id;

        if (preg_match('/^[a-zA-Z0-9\s.-]*$/', $address) !== 1 || !$newAddress->save()) {
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
                    'postCodeID' => 'postCodeID'
                ]
            ],
            'postCode' => [
                'class' => PostCode::class,
                'field' => 'postCode',
                'related' => [
                    'localeID' => 'localeID'
                ]
            ],
            'locale'   => [
                'class' => Locale::class,
                'field' => 'name',
                'related' => [
                    'countryID' => 'countryID'
                ]
            ]
        ];
        
        $orderKeys = array_keys($orderFlow);

        $position = 0;
        $addressID = null;
        $postCodeID = null;
        $localeID = null;
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
                ${$value . 'ID'} = $record->id;

                if ($isLast) {
                    unset($orderFlow[$value]);
                    reset($orderFlow);
                } else {
                    $prevPosition = $position - 1;
                    $orderFlow = array_slice($orderFlow, 0, $prevPosition >= 0 ? $prevPosition : 0);
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
