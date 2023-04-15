<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\Product;
use Phlexus\Modules\Shop\Models\ProductAttribute;
use Phlexus\Modules\Generic\Forms\BaseForm;
use Phlexus\Libraries\Media\Models\Media;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\File;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Http\ResponseInterface;
use Phalcon\Tag;

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
        $title = $this->translation->setTypePage()->_('title-products-manager');

        Tag::setTitle($title);

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
                'name'     => 'name',
                'type'     => Text::class,
                'required' => true
            ],
            [
                'name'     => 'description',
                'type'     => Text::class,
                'required' => false
            ],
            [
                'name'     => 'price',
                'type'     => Text::class,
                'required' => true
            ],
            [
                'name'     => 'isSubscription',
                'type'     => Check::class,
                'value'    => 1
            ],
            [
                'name'    => 'imageID',
                'type'    => File::class,
                'related' => Media::class
            ]
        ];

        $this->setFormFields($formFields);

        $form->setFields($this->parseFields($formFields));

        $this->setForm($form);

        $this->setViewFields(['id', 'name', 'price', 'isSubscription']);

        $this->setRelatedViews([
            'link-product-attributes' => $this->url->get('shop/product_attribute')
        ]);
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

    /**
     * Override Save Action
     *
     * @return ResponseInterface
     */
    public function saveAction(): ResponseInterface
    {
        $isSubscription = null;

        if ($this->request->isPost()) {
            $isSubscription = $this->request->getPost('isSubscription', null, null);
            
            if ($isSubscription === null) {
                $_POST['isSubscription'] = '0';
            }
        }

        $response = $this->traitSaveAction();

        if (!$this->isModelEdit() && $isSubscription === '1') {
            $product = $this->getModel();

            $productAttributes = ProductAttribute::getSubscriptionAttributes();
            $this->getModel()->setAttributes($productAttributes);
        }

        return $response;
    }
}
