<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class PageController extends Controller
{
    /** @var array<string, array{title: string, description: string, view: string}> */
    private const PAGES = [
        'about' => ['title' => 'About StayIn', 'description' => 'Who we are and how StayIn works.', 'view' => 'pages/about'],
        'contact' => ['title' => 'Contact & support', 'description' => 'Reach the StayIn team.', 'view' => 'pages/contact'],
        'privacy' => ['title' => 'Privacy Policy', 'description' => 'How StayIn handles your data.', 'view' => 'pages/privacy'],
        'terms' => ['title' => 'Terms & Conditions', 'description' => 'The rules of the StayIn marketplace.', 'view' => 'pages/terms'],
        'help' => ['title' => 'Help centre', 'description' => 'Answers to common questions.', 'view' => 'pages/help'],
    ];

    public function about(Request $request): Response
    {
        return $this->renderStatic('about');
    }

    public function contact(Request $request): Response
    {
        return $this->renderStatic('contact');
    }

    public function privacy(Request $request): Response
    {
        return $this->renderStatic('privacy');
    }

    public function terms(Request $request): Response
    {
        return $this->renderStatic('terms');
    }

    public function help(Request $request): Response
    {
        return $this->renderStatic('help');
    }

    private function renderStatic(string $key): Response
    {
        $page = self::PAGES[$key];

        return $this->view($page['view'], [
            'metaTitle' => $page['title'] . ' · StayIn',
            'metaDescription' => $page['description'],
        ]);
    }
}
