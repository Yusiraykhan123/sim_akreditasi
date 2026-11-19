<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\Element;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'categoryCount' => Category::count(),
            'elementCount' => Element::count(),
            'documentCount' => Document::count(),
            'recentDocuments' => Document::with('element.category')->latest()->take(6)->get(),
            'categoryGroups' => Category::withCount('elements')->orderBy('id')->get()->groupBy('group_name'),
        ]);
    }
}
