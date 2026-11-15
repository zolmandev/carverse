<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Fitment;
use App\Models\HistoryShare;
use App\Models\Reminder;
use App\Models\ServiceRecord;
use App\Models\ServiceRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MobileVehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->vehicles()->latest()->get()->map(fn (Vehicle $vehicle) => $this->vehicleData($vehicle)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        unset($data['photo']);
        $vehicle = $request->user()->vehicles()->create($data);

        if ($request->hasFile('photo')) {
            $vehicle->update(['photo_path' => $request->file('photo')->store('photos', 'local')]);
        }

        return response()->json(['data' => $this->vehicleData($vehicle->fresh())], 201);
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->owned($request, $vehicle);

        return response()->json(['data' => $this->vehicleData($vehicle)]);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->owned($request, $vehicle);
        $data = $request->validate($this->rules(true));

        if (isset($data['mileage']) && $data['mileage'] < $vehicle->mileage) {
            throw ValidationException::withMessages(['mileage' => 'Mileage cannot be lower than the current reading.']);
        }

        $vehicle->update($data);

        return response()->json(['data' => $this->vehicleData($vehicle->fresh())]);
    }

    public function photo(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->owned($request, $vehicle);
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120']]);
        $oldPath = $vehicle->photo_path;
        $newPath = $request->file('photo')->store('photos', 'local');
        $vehicle->update(['photo_path' => $newPath]);

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json(['data' => $this->vehicleData($vehicle->fresh())]);
    }

    public function image(Request $request, Vehicle $vehicle): BinaryFileResponse
    {
        $this->owned($request, $vehicle);
        abort_unless($vehicle->photo_path, 404);

        return response()->file(Storage::disk('local')->path($vehicle->photo_path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function destroyPhoto(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->owned($request, $vehicle);
        $oldPath = $vehicle->photo_path;
        $vehicle->update(['photo_path' => null]);

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json(['data' => $this->vehicleData($vehicle->fresh())]);
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->owned($request, $vehicle);
        $hasHistory = ServiceRequest::where('vehicle_id', $vehicle->id)->exists()
            || ServiceRecord::where('vehicle_id', $vehicle->id)->exists()
            || Reminder::where('vehicle_id', $vehicle->id)->exists()
            || HistoryShare::where('vehicle_id', $vehicle->id)->exists()
            || Fitment::where('vehicle_id', $vehicle->id)->exists();

        if ($hasHistory) {
            return response()->json([
                'message' => 'This car has retained service history and cannot be deleted.',
                'code' => 'VEHICLE_HAS_HISTORY',
            ], 409);
        }

        $photoPath = $vehicle->photo_path;
        $vehicle->delete();
        if ($photoPath) {
            Storage::disk('local')->delete($photoPath);
        }

        return response()->json([], 204);
    }

    private function owned(Request $request, Vehicle $vehicle): void
    {
        abort_unless($vehicle->user_id === $request->user()->id, 404);
    }

    private function vehicleData(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'variant' => $vehicle->variant,
            'engine_code' => $vehicle->engine_code,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'mileage' => $vehicle->mileage,
            'mileage_date' => $vehicle->mileage_date?->format('Y-m-d'),
            'has_identifier' => filled($vehicle->identifier),
            'photo_url' => $vehicle->photo_path ? url('/api/v1/cars/'.$vehicle->id.'/photo') : null,
            'created_at' => $vehicle->created_at?->toIso8601String(),
            'updated_at' => $vehicle->updated_at?->toIso8601String(),
        ];
    }

    private function rules(bool $partial = false): array
    {
        return [
            'photo' => [$partial ? 'prohibited' : 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'make' => [...($partial ? ['sometimes'] : []), 'required', 'string', 'max:80'],
            'model' => [...($partial ? ['sometimes'] : []), 'required', 'string', 'max:80'],
            'year' => [...($partial ? ['sometimes'] : []), 'required', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'variant' => [...($partial ? ['sometimes'] : []), 'nullable', 'string', 'max:100'],
            'engine_code' => [...($partial ? ['sometimes'] : []), 'nullable', 'string', 'max:80'],
            'fuel_type' => [...($partial ? ['sometimes'] : []), 'required', 'in:Petrol,Diesel,Hybrid,Electric'],
            'transmission' => [...($partial ? ['sometimes'] : []), 'required', 'in:Automatic,Manual,CVT'],
            'identifier' => [...($partial ? ['sometimes'] : []), 'nullable', 'string', 'min:4', 'max:80'],
            'mileage' => [...($partial ? ['sometimes'] : []), 'required', 'integer', 'min:0', 'max:3000000'],
            'mileage_date' => [$partial ? 'required_with:mileage' : 'required', 'date', 'before_or_equal:today'],
        ];
    }
}
