<?php

namespace Database\Seeders;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Community;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CarCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $snapshot = json_decode(gzdecode(file_get_contents(database_path('data/car-catalog.json.gz'))), true, 512, JSON_THROW_ON_ERROR);
        $now = now();
        DB::transaction(function () use ($snapshot, $now) {
            foreach (array_chunk($snapshot['brands'], 200) as $chunk) {
                $rows = [];
                foreach ($chunk as $b) {
                    $rows[] = ['name' => $b['name'], 'slug' => $b['slug'], 'logo_asset' => $b['logo_asset'], 'source' => $b['source'], 'source_url' => $b['source_url'], 'created_at' => $now, 'updated_at' => $now];
                }DB::table('car_brands')->insertOrIgnore($rows);
            }
            $brandIds = DB::table('car_brands')->pluck('id', 'slug');
            foreach (array_chunk($snapshot['models'], 200) as $chunk) {
                $rows = [];
                foreach ($chunk as $m) {
                    $rows[] = ['car_brand_id' => $brandIds[$m['brand']], 'name' => $m['name'], 'slug' => $m['slug'], 'source' => $m['source'], 'source_url' => $m['source_url'], 'created_at' => $now, 'updated_at' => $now];
                }DB::table('car_models')->insertOrIgnore($rows);
            }
            $brandSlugs = $brandIds->flip();
            $modelIds = DB::table('car_models')->select('id', 'car_brand_id', 'slug')->get()->mapWithKeys(fn ($m) => [$brandSlugs[$m->car_brand_id].'/'.$m->slug => $m->id]);
            foreach (array_chunk($snapshot['trims'], 200) as $chunk) {
                $rows = [];
                foreach ($chunk as $t) {
                    $rows[] = ['car_model_id' => $modelIds[$t['model']], 'name' => $t['name'], 'source_key' => $t['key'], 'model_year' => $t['model_year'], 'market' => $t['market'], 'kind' => $t['kind'], 'source' => $t['source'], 'source_url' => $t['source_url'], 'created_at' => $now, 'updated_at' => $now];
                }DB::table('car_trims')->insertOrIgnore($rows);
            }
            // Preserve imported-car names already recorded locally; do not discard historical group labels.
            foreach (Vehicle::select('make', 'model')->distinct()->get() as $v) {
                $brand = CarBrand::firstOrCreate(['slug' => Str::slug($v->make)], ['name' => $v->make, 'source' => 'Existing vehicle record']);
                CarModel::firstOrCreate(['car_brand_id' => $brand->id, 'slug' => Str::slug($v->model)], ['name' => $v->model, 'source' => 'Existing vehicle record']);
            }
            $brands = CarBrand::orderByRaw('LENGTH(name) DESC')->get();
            foreach (Community::whereNull('car_model_id')->get() as $group) {
                foreach ($brands as $brand) {
                    $prefix = $brand->name.' ';
                    if (Str::startsWith(Str::lower($group->model), Str::lower($prefix))) {
                        $name = trim(substr($group->model, strlen($prefix)));
                        $model = CarModel::firstOrCreate(['car_brand_id' => $brand->id, 'slug' => Str::slug($name)], ['name' => $name, 'source' => 'Existing community label']);
                        $group->update(['car_brand_id' => $brand->id, 'car_model_id' => $model->id]);
                        break;
                    }
                }
            }
        });
        $this->command?->info('Car catalog seeded without replacing administrator edits.');
    }
}
