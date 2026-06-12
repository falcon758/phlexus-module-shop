<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\ResponseInterface;
use Phlexus\Modules\Shop\Models\User;

/**
 * Abstract Shop Controller
 *
 * @package Phlexus\Modules\Shop\Controllers
 */
abstract class AbstractController extends Controller
{
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

    protected function getAuthenticatedUser(): ?User
    {
        return User::getUser();
    }

    protected function redirectIfGuest(string $route = '/user'): ?ResponseInterface
    {
        if ($this->getAuthenticatedUser() !== null) {
            return null;
        }

        return $this->response->redirect($route);
    }
}
