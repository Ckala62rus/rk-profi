<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inertia-страницы админ-панели (оболочка Metronic).
 */
class AdminPageController extends Controller
{
    /**
     * Страница входа в админку.
     *
     * @return Response
     */
    public function login(): Response
    {
        return Inertia::render('Admin/Login');
    }

    /**
     * Дашборд админки.
     *
     * @return Response
     */
    public function dashboard(): Response
    {
        return Inertia::render('Admin/Dashboard');
    }

    /**
     * Список категорий.
     *
     * @return Response
     */
    public function categories(): Response
    {
        return Inertia::render('Admin/Categories/Index');
    }

    /**
     * Список товаров.
     *
     * @return Response
     */
    public function products(): Response
    {
        return Inertia::render('Admin/Products/Index');
    }

    /**
     * Список заявок.
     *
     * @return Response
     */
    public function leads(): Response
    {
        return Inertia::render('Admin/Leads/Index');
    }

    /**
     * Настройки контактов.
     *
     * @return Response
     */
    public function settings(): Response
    {
        return Inertia::render('Admin/Settings/Contacts');
    }

    /**
     * CMS: список контентных страниц.
     *
     * @return Response
     */
    public function pages(): Response
    {
        return Inertia::render('Admin/Pages/Index');
    }
}
