<?php

namespace App\Http\Controllers;

use App\Services\MarkdownBlogService;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private readonly MarkdownBlogService $blogService)
    {
    }

    public function index(): View
    {
        return view('blog.index', [
            'posts' => $this->blogService->all(),
        ]);
    }

    public function show(string $slug): View
    {
        $post = $this->blogService->findBySlug($slug);

        abort_if($post === null, 404);

        return view('blog.show', [
            'post' => $post,
        ]);
    }
}
