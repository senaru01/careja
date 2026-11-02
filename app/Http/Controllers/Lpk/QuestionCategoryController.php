<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lpk\QuestionCategoryRequest;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionCategoryController extends Controller
{
    /**
     * Question bank overview: categories with their question counts.
     */
    public function index(Request $request): View
    {
        return view('lpk.bank.index', [
            'categories' => $request->user()->organization->questionCategories()->withCount('questions')->get(),
        ]);
    }

    public function store(QuestionCategoryRequest $request): RedirectResponse
    {
        $category = $request->user()->organization->questionCategories()->create($request->validated());

        return redirect()->route('lpk.categories.show', $category)->with('status', "Kategori \"{$category->name}\" dibuat.");
    }

    /**
     * Questions of one category, with search, add form and Excel import.
     */
    public function show(Request $request, QuestionCategory $category): View
    {
        Gate::authorize('manage', $category);

        $search = $request->string('search')->trim()->toString();

        return view('lpk.bank.show', [
            'category' => $category->loadCount('questions'),
            'questions' => $category->questions()
                ->with('options')
                ->when($search !== '', fn ($query) => $query->where('question', 'like', "%{$search}%"))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function edit(QuestionCategory $category): View
    {
        Gate::authorize('manage', $category);

        return view('lpk.bank.category-edit', ['category' => $category]);
    }

    public function update(QuestionCategoryRequest $request, QuestionCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('lpk.categories.show', $category)->with('status', 'Kategori diperbarui.');
    }

    /**
     * Delete a category and its bank questions. Exams that already copied
     * questions from it keep their own copies.
     */
    public function destroy(QuestionCategory $category): RedirectResponse
    {
        Gate::authorize('manage', $category);

        $category->delete();

        return redirect()->route('lpk.categories.index')->with('status', "Kategori \"{$category->name}\" dihapus.");
    }
}
