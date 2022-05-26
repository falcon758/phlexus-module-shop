<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\ProductAttribute;
use Phlexus\Modules\Generic\Forms\BaseForm;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\File;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Http\ResponseInterface;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

/**
 * Class ProductAttribute
 *
 * @package Phlexus\Modules\Shop\Controllers
 */
final class ProductAttributeController extends AbstractController
{

    const PAGE_LIMIT = 25;

    use \Phlexus\Modules\Generic\Actions\CreateAction;
    use \Phlexus\Modules\Generic\Actions\EditAction;
    use \Phlexus\Modules\Generic\Actions\DeleteAction;
    use \Phlexus\Modules\Generic\Actions\ViewAction;
    use \Phlexus\Modules\Generic\Actions\SaveAction {
        saveAction as protected traitSaveAction;
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize(): void
    {
        $title = $this->translation->setTypePage()->_('title-product-attributes-manager');

        $this->tag->setTitle($title);

        $mainView = $this->view->getMainView();

        $this->view->setMainView(preg_replace('/\/public$/', '/default', $mainView));

        $this->setModel(new ProductAttribute);

        $productAttParams = [
            'active'    => ProductAttribute::ENABLED,
            'productID' => (int) $this->request->get('related', null, 0),
        ];

        $this->setRecords(ProductAttribute::getModelPaginator($productAttParams, (int) $this->request->get('p', null, 1)));

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
                'name'     => 'name',
                'type'     => Text::class,
                'required' => true
            ],
            [
                'name'     => 'value',
                'type'     => Text::class,
                'required' => true
            ]
        ];

        $this->setFormFields($formFields);

        $form->setFields($this->parseFields($formFields));

        $this->setForm($form);

        $this->setViewFields(['id', 'name', 'value']);
    }



    /**
     * Override Save Action
     *
     * @return ResponseInterface
     */
    public function saveAction(): ResponseInterface
    {
        $response = $this->traitSaveAction();

        $model = $this->getModel();

        $productID = (isset($model->productID) ? $model->productID : 0);

        if ($productID === 0) {
            return $response;
        }

        return $this->response->redirect('/shop/product_attribute?related=' . $productID);
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
