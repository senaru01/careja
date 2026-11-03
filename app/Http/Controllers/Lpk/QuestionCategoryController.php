<?php

namespace App\Http\Controllers\Lpk;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lpk\QuestionCategoryRequest;
use App\Models\Organization;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionCategoryController extends Controller
{
    /**
     * Question bank overview: categories with their question counts. The
     * super admin sees the categories of every LPK.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('lpk.bank.index', [
            'categories' => QuestionCategory::query()
                ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('organization_id', $user->organization_id))
                ->with('organization')
                ->withCount('questions')
                ->orderBy('organization_id')
                ->orderBy('id')
                ->get(),
            'lpks' => $user->isSuperAdmin()
                ? Organization::where('type', OrganizationType::Lpk)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ]);
    }

    public function store(QuestionCategoryRequest $request): RedirectResponse
    {
        $category = Organization::findOrFail($request->owningOrganizationId())
            ->questionCategories()
            ->create($request->safe()->only(['name', 'description']));

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
        $category->update($request->safe()->only(['name', 'description']));

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
