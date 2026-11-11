<?php

namespace Tests\Feature;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\CarTrim;
use App\Models\Community;
use App\Models\User;
use Database\Seeders\CarCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CarCatalogTest extends TestCase
{
    use RefreshDatabase;

    private CarBrand $brand;

    private CarModel $model;

    private CarTrim $trim;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->brand = CarBrand::create(['name' => 'Toyota', 'slug' => 'toyota']);
        $this->model = CarModel::create(['car_brand_id' => $this->brand->id, 'name' => 'Fielder', 'slug' => 'fielder']);
        $this->trim = CarTrim::create(['car_model_id' => $this->model->id, 'name' => 'Example grade', 'source_key' => 'test-grade', 'kind' => 'grade', 'market' => 'Kenya', 'model_year' => 2016]);
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function groupData(): array
    {
        return ['name' => 'Fielder Club', 'car_brand_id' => $this->brand->id, 'car_model_id' => $this->model->id, 'car_trim_id' => $this->trim->id, 'url' => 'https://chat.whatsapp.com/Example123'];
    }

    public function test_catalog_management_and_option_endpoints_require_admin(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/cars/brands', ['name' => 'Test', 'is_active' => 1])->assertForbidden();
        $this->get('/cars/brands/'.$this->brand->id.'/models')->assertForbidden();
        $this->get('/cars/models/'.$this->model->id.'/trims')->assertForbidden();
        $this->delete('/cars/catalog/brands/'.$this->brand->id)->assertForbidden();
    }

    public function test_group_stores_separate_brand_model_and_trim(): void
    {
        $this->admin();
        $this->post('/communities', $this->groupData())->assertSessionHasNoErrors();
        $g = Community::firstOrFail();
        $this->assertSame($this->brand->id, $g->car_brand_id);
        $this->assertSame($this->model->id, $g->car_model_id);
        $this->assertSame($this->trim->id, $g->car_trim_id);
        $this->assertSame('Toyota Fielder', $g->model);
        $this->get('/app/admin')->assertInertia(fn (Assert $p) => $p->where('groups.data.0.brand.name', 'Toyota')->where('groups.data.0.car_model.name', 'Fielder')->where('groups.data.0.trim.name', 'Example grade'));
    }

    public function test_group_rejects_cross_brand_models_and_cross_model_trims(): void
    {
        $this->admin();
        $other = CarBrand::create(['name' => 'Mazda', 'slug' => 'mazda']);
        $model = CarModel::create(['car_brand_id' => $other->id, 'name' => 'Demio', 'slug' => 'demio']);
        $this->post('/communities', array_replace($this->groupData(), ['car_brand_id' => $other->id]))->assertSessionHasErrors('car_model_id');
        $this->post('/communities', array_replace($this->groupData(), ['car_brand_id' => $other->id, 'car_model_id' => $model->id]))->assertSessionHasErrors('car_trim_id');
        $this->assertDatabaseCount('communities', 0);
    }

    public function test_admin_can_add_brand_model_trim_and_edit_records(): void
    {
        $this->admin();
        $this->post('/cars/brands', ['name' => 'Pilot Motors', 'source' => 'Admin reference', 'is_active' => 1])->assertSessionHasNoErrors();
        $brand = CarBrand::where('slug', 'pilot-motors')->firstOrFail();
        $this->post('/cars/models', ['car_brand_id' => $brand->id, 'name' => 'Model One', 'is_active' => 1])->assertSessionHasNoErrors();
        $model = $brand->models()->firstOrFail();
        $this->post('/cars/trims', ['car_brand_id' => $brand->id, 'car_model_id' => $model->id, 'name' => 'Touring', 'model_year' => 2026, 'market' => 'Kenya', 'kind' => 'grade', 'is_active' => 1])->assertSessionHasNoErrors();
        $trim = $model->trims()->firstOrFail();
        $this->put('/cars/trims/'.$trim->id, ['car_brand_id' => $brand->id, 'car_model_id' => $model->id, 'name' => 'Touring Plus', 'model_year' => 2026, 'market' => 'Kenya', 'kind' => 'grade', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame('Touring Plus', $trim->fresh()->name);
        $this->assertDatabaseHas('audit_events', ['action' => 'car_trim.updated']);
        $this->get('/app/admin?tab=cars&catalog_level=trims&catalog_brand_id='.$brand->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('catalog.level', 'trims')->has('catalog.trims.data', 1));
    }

    public function test_archiving_preserves_groups_and_removes_new_choices(): void
    {
        $this->admin();
        $this->post('/communities', $this->groupData())->assertSessionHasNoErrors();
        $this->delete('/cars/catalog/models/'.$this->model->id)->assertRedirect();
        $this->assertDatabaseCount('communities', 1);
        $this->getJson('/cars/brands/'.$this->brand->id.'/models')->assertExactJson(['options' => []]);
        $this->post('/communities', $this->groupData())->assertSessionHasErrors('car_model_id');
        $this->getJson('/cars/brands/'.$this->brand->id.'/models?all=1')->assertJsonCount(1, 'options');
    }

    public function test_seed_snapshot_is_idempotent_and_does_not_overwrite_admin_changes(): void
    {
        $this->seed(CarCatalogSeeder::class);
        $counts = [CarBrand::count(), CarModel::count(), CarTrim::count()];
        $this->assertGreaterThan(400, $counts[0]);
        $this->assertGreaterThan(11000, $counts[1]);
        $this->assertSame(52125, $counts[2]);
        $this->brand->update(['name' => 'Toyota local display', 'is_active' => false]);
        $this->seed(CarCatalogSeeder::class);
        $this->assertSame($counts, [CarBrand::count(), CarModel::count(), CarTrim::count()]);
        $this->assertSame('Toyota local display', $this->brand->fresh()->name);
        $this->assertFalse($this->brand->fresh()->is_active);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_uploaded_brand_logo_is_validated_stored_privately_and_served_as_image(): void
    {
        Storage::fake('local');
        $this->admin();
        $logo = UploadedFile::fake()->createWithContent('brand.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jN7cAAAAASUVORK5CYII='));
        $this->put('/cars/brands/'.$this->brand->id, ['name' => 'Toyota', 'is_active' => 1, 'logo' => $logo])->assertSessionHasNoErrors();
        $b = $this->brand->fresh();
        Storage::disk('local')->assertExists($b->logo_path);
        $this->assertArrayNotHasKey('logo_path', $b->toArray());
        $this->get('/cars/brands/'.$b->id.'/logo')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->put('/cars/brands/'.$b->id, ['name' => 'Toyota', 'is_active' => 1, 'logo' => UploadedFile::fake()->createWithContent('unsafe.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])->assertSessionHasErrors('logo');
    }

    public function test_active_and_archived_brand_tabs_are_separate_and_searchable(): void
    {
        $this->admin();
        $this->brand->update(['is_active' => false]);
        CarBrand::create(['name' => 'Mazda', 'slug' => 'mazda']);
        CarBrand::create(['name' => 'Honda', 'slug' => 'honda', 'is_active' => false]);
        $this->get('/app/admin')->assertInertia(fn (Assert $p) => $p->has('carBrands', 1)->where('carBrands.0.name', 'Mazda')->where('carBrands.0.is_active', true));
        $this->post('/communities', $this->groupData())->assertSessionHasErrors('car_brand_id');
        $this->get('/app/admin?tab=cars&catalog_level=brands')->assertInertia(fn (Assert $p) => $p->has('catalog.brands.data', 1)->where('catalog.brands.data.0.name', 'Mazda'));
        $this->get('/app/admin?tab=cars&catalog_level=archived-brands&q=Toyota')->assertInertia(fn (Assert $p) => $p->where('catalog.level', 'archived-brands')->has('catalog.brands.data', 1)->where('catalog.brands.data.0.name', 'Toyota')->where('catalog.brands.data.0.is_active', false));
        $this->model->update(['is_active' => false]);
        $this->get('/app/admin?tab=cars&catalog_level=models')->assertInertia(fn (Assert $p) => $p->has('catalog.models.data', 1)->where('catalog.models.data.0.id', $this->model->id));
    }

    public function test_only_admin_can_restore_an_archived_brand(): void
    {
        $this->brand->update(['is_active' => false]);
        $this->actingAs(User::factory()->create())->post('/cars/brands/'.$this->brand->id.'/restore')->assertForbidden();
        $this->admin();
        $this->post('/cars/brands/'.$this->brand->id.'/restore')->assertRedirect();
        $this->assertTrue($this->brand->fresh()->is_active);
        $this->assertDatabaseHas('audit_events', ['action' => 'car_brand.restored']);
        $this->get('/app/admin?tab=cars&catalog_level=archived-brands')->assertInertia(fn (Assert $p) => $p->has('catalog.brands.data', 0));
        $this->get('/app/admin?tab=cars&catalog_level=brands')->assertInertia(fn (Assert $p) => $p->has('catalog.brands.data', 1)->where('catalog.brands.data.0.id', $this->brand->id));
    }
}
