<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\Product;
use Phlexus\Modules\Generic\Forms\BaseForm;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\File;
use Phalcon\Forms\Element\Hidden;
use Phlexus\Libraries\Media\Models\Media;

/**
 * Class Product
 *
 * @package Phlexus\Modules\Shop\Controllers
 */
final class ProductController extends AbstractController
{
    use \Phlexus\Modules\Generic\Actions\CreateAction;
    use \Phlexus\Modules\Generic\Actions\EditAction;
    use \Phlexus\Modules\Generic\Actions\DeleteAction;
    use \Phlexus\Modules\Generic\Actions\ViewAction;
    use \Phlexus\Modules\Generic\Actions\SaveAction;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $mainView = $this->view->getMainView();

        $this->view->setMainView(preg_replace('/\/public$/', '/default', $mainView));

        $this->setModel(new Product);

        $form = new BaseForm(!$this->isSave());

        $formFields = [
            [
                'name' => 'csrf',
                'type' => Hidden::class
            ],
            [
                'name' => 'id',
                'type' => Hidden::class
            ],
            [
                'name' => 'name',
                'type' => Text::class,
                'required' => true
            ],
            [
                'name' => 'price',
                'type' => Text::class,
                'required' => true
            ],
            [
                'name' => 'imageID',
                'type' => File::class,
                'related' => Media::class
            ]
        ];

        $this->setFormFields($formFields);

        $form->setFields($this->parseFields($formFields));

        $this->setForm($form);

        $this->setViewFields(['id', 'name', 'price']);
    }

    /**
     * Is save
     *
     * @return bool
     */
    private function isSave()
    {
        return $this->dispatcher->getActionName() === 'save';
    }
}
