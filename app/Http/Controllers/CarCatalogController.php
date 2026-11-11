<?php

namespace App\Http\Controllers;

use App\Actions\Workflow;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\CarTrim;
use App\Policies\WorkspacePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CarCatalogController extends Controller
{
    private function authorizeAdmin(Request $r): void
    {
        abort_unless((new WorkspacePolicy)->admin($r->user()), 403);
    }

    public function page(Request $r): array
    {
        $d = $r->validate(['catalog_level' => 'nullable|in:brands,models,trims', 'catalog_brand_id' => 'nullable|exists:car_brands,id', 'catalog_model_id' => 'nullable|exists:car_models,id', 'q' => 'nullable|string|max:160']);
        $search = $d['q'] ?? null;
        $brand = $d['catalog_brand_id'] ?? null;
        $model = $d['catalog_model_id'] ?? null;

        return ['level' => $d['catalog_level'] ?? 'brands', 'filters' => $d, 'stats' => ['brands' => CarBrand::count(), 'models' => CarModel::count(), 'trims' => CarTrim::count(), 'logos' => CarBrand::where(fn ($q) => $q->whereNotNull('logo_asset')->orWhereNotNull('logo_path'))->count()],
            'brands' => CarBrand::withCount('models')->when($search, fn ($q, $s) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($s).'%']))->orderBy('name')->paginate(20, ['*'], 'brands_page')->withQueryString(),
            'models' => CarModel::with('brand')->withCount('trims')->when($brand, fn ($q) => $q->where('car_brand_id', $brand))->when($search, fn ($q, $s) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($s).'%']))->orderBy('name')->paginate(20, ['*'], 'models_page')->withQueryString(),
            'trims' => CarTrim::with('model.brand')->when($model, fn ($q) => $q->where('car_model_id', $model))->when($brand, fn ($q) => $q->whereHas('model', fn ($q) => $q->where('car_brand_id', $brand)))->when($search, fn ($q, $s) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($s).'%']))->orderByDesc('model_year')->orderBy('name')->paginate(25, ['*'], 'trims_page')->withQueryString()];
    }

    public function models(Request $r, CarBrand $brand)
    {
        $this->authorizeAdmin($r);

        return response()->json(['options' => $brand->models()->when(! $r->boolean('all'), fn ($q) => $q->where('is_active', true))->orderBy('name')->get(['id', 'name', 'is_active'])->map(fn ($m) => ['value' => $m->id, 'label' => $m->name.($m->is_active ? '' : ' (archived)')])]);
    }

    public function trims(Request $r, CarModel $model)
    {
        $this->authorizeAdmin($r);

        return response()->json(['options' => $model->trims()->when(! $r->boolean('all'), fn ($q) => $q->where('is_active', true))->orderByDesc('model_year')->orderBy('name')->get(['id', 'name', 'model_year', 'market', 'is_active'])->map(fn ($t) => ['value' => $t->id, 'label' => $t->name.($t->model_year ? ' · '.$t->model_year : '').' · '.$t->market.($t->is_active ? '' : ' (archived)')])]);
    }

    private function metaRules(): array
    {
        return ['name' => 'required|string|max:160', 'source' => 'nullable|string|max:255', 'source_url' => 'nullable|url|max:2000', 'is_active' => 'required|boolean'];
    }

    public function brand(Request $r, ?CarBrand $brand = null)
    {
        $this->authorizeAdmin($r);
        $brand = $brand && $brand->exists ? $brand : new CarBrand;
        $d = $r->validate($this->metaRules() + ['logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $slug = $brand->exists ? $brand->slug : Str::slug($d['name']);
        if (! $slug || CarBrand::where('slug', $slug)->when($brand->exists, fn ($q) => $q->whereKeyNot($brand->id))->exists()) {
            throw ValidationException::withMessages(['name' => 'A brand with this name already exists, or the name is invalid.']);
        }
        $previous = $brand->exists ? $brand->only('name', 'is_active', 'source', 'source_url') : null;
        DB::transaction(function () use ($r, $brand, $d, $slug, $previous) {
            $brand->fill(collect($d)->except('logo')->all() + ['slug' => $slug]);
            if ($r->hasFile('logo')) {
                $brand->logo_path = $r->file('logo')->store('brand-logos', 'local');
            }$brand->save();
            Workflow::audit($previous ? 'car_brand.updated' : 'car_brand.created', $brand, ['previous' => $previous]);
        });

        return redirect('/app/admin?tab=cars&catalog_level=brands')->with('message', 'Brand saved.');
    }

    public function model(Request $r, ?CarModel $model = null)
    {
        $this->authorizeAdmin($r);
        $model = $model && $model->exists ? $model : new CarModel;
        $d = $r->validate($this->metaRules() + ['car_brand_id' => 'required|exists:car_brands,id']);
        $slug = $model->exists ? $model->slug : Str::slug($d['name']);
        if ($model->exists && $model->car_brand_id !== (int) $d['car_brand_id']) {
            throw ValidationException::withMessages(['car_brand_id' => 'A model cannot be moved to another brand. Archive it and create a new model instead.']);
        }
        if (! $slug || CarModel::where('car_brand_id', $d['car_brand_id'])->where('slug', $slug)->when($model->exists, fn ($q) => $q->whereKeyNot($model->id))->exists()) {
            throw ValidationException::withMessages(['name' => 'This model already exists for the selected brand.']);
        }
        $previous = $model->exists ? $model->getAttributes() : null;
        DB::transaction(function () use ($model, $d, $slug, $previous) {
            $model->fill($d + ['slug' => $slug])->save();
            Workflow::audit($previous ? 'car_model.updated' : 'car_model.created', $model, ['previous' => $previous]);
        });

        return redirect('/app/admin?tab=cars&catalog_level=models&catalog_brand_id='.$d['car_brand_id'])->with('message', 'Model saved.');
    }

    public function trim(Request $r, ?CarTrim $trim = null)
    {
        $this->authorizeAdmin($r);
        $trim = $trim && $trim->exists ? $trim : new CarTrim;
        $d = $r->validate($this->metaRules() + ['car_brand_id' => 'required|exists:car_brands,id', 'car_model_id' => ['required', Rule::exists('car_models', 'id')->where('car_brand_id', $r->input('car_brand_id'))], 'model_year' => 'nullable|integer|min:1886|max:'.(date('Y') + 2), 'market' => 'required|string|max:100', 'kind' => 'required|in:grade,variant']);
        if ($trim->exists && $trim->car_model_id !== (int) $d['car_model_id']) {
            throw ValidationException::withMessages(['car_model_id' => 'A trim cannot be moved to another model. Archive it and create a new trim instead.']);
        }
        $key = $trim->exists ? $trim->source_key : hash('sha256', Str::lower($d['name']).'|'.($d['model_year'] ?? '').'|'.Str::lower($d['market']));
        if (CarTrim::where('car_model_id', $d['car_model_id'])->where('source_key', $key)->when($trim->exists, fn ($q) => $q->whereKeyNot($trim->id))->exists()) {
            throw ValidationException::withMessages(['name' => 'This trim, year and market combination already exists.']);
        }
        $previous = $trim->exists ? $trim->getAttributes() : null;
        DB::transaction(function () use ($trim, $d, $key, $previous) {
            $trim->fill(collect($d)->except('car_brand_id')->all() + ['source_key' => $key])->save();
            Workflow::audit($previous ? 'car_trim.updated' : 'car_trim.created', $trim, ['previous' => $previous]);
        });

        return redirect('/app/admin?tab=cars&catalog_level=trims&catalog_brand_id='.$d['car_brand_id'].'&catalog_model_id='.$d['car_model_id'])->with('message', 'Trim or variant saved.');
    }

    public function archive(Request $r, string $kind, int $id)
    {
        $this->authorizeAdmin($r);
        $record = match ($kind) {
            'brands' => CarBrand::findOrFail($id),'models' => CarModel::findOrFail($id),'trims' => CarTrim::findOrFail($id),default => abort(404)
        };
        DB::transaction(function () use ($record) {
            $record->update(['is_active' => false]);
            Workflow::audit('car_catalog.archived', $record);
        });

        return back()->with('message','Archived. Existing group associations are preserved.');
    }

    public function logo(CarBrand $brand)
    {
        abort_unless($brand->logo_path,404);

        return response()->file(Storage::disk('local')->path($brand->logo_path),['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=3600']);
    }
}
