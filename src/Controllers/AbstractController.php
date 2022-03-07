<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Mvc\Controller;

/**
 * Abstract Shop Controller
 *
 * @package Phlexus\Modules\Shop\Controllers
 */
abstract class AbstractController extends Controller
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->tag->appendTitle(' - Phlexus Shop');
    }

    /**
     * Get Base Position
     *
     * @return string Current base position (module/controller)
     */
    public function getBasePosition(): string
    {
        $module = strtolower($this->dispatcher->getModuleName());
        $controller = strtolower($this->dispatcher->getControllerName());

        if ($module !== $controller) {
            $basePosition = $module . '/' . $controller;
        } else {
            $basePosition = $controller;
        }

        return '/' . $basePosition;
    }
}
