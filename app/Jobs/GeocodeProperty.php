<?php

namespace App\Jobs;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeProperty implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $propertyId)
    {
    }

    public function handle(): void
    {
        $property = Property::find($this->propertyId);
        if (! $property) {
            return;
        }

        $queries = [
            collect([$property->house_number, $property->street, $property->city, $property->province, 'Philippines'])->filter()->implode(', '),
            collect([$property->city, $property->province, 'Philippines'])->filter()->implode(', '),
        ];

        foreach (array_unique(array_filter($queries)) as $index => $address) {
            if ($index > 0) {
                sleep(1);
            }

            try {
                $response = Http::timeout(10)->withHeaders([
                    'User-Agent' => 'LJSRealtyApp/1.0 (ljsrealty@gmail.com)',
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'q' => $address,
                    'limit' => 1,
                    'countrycodes' => 'ph',
                ]);
            } catch (ConnectionException $exception) {
                Log::warning('Property geocoding request failed and will be retried.', ['property_id' => $this->propertyId]);
                throw $exception;
            }

            if (! $response->successful()) {
                throw new \RuntimeException('Property geocoding returned HTTP ' . $response->status());
            }

            $result = $response->json()[0] ?? null;
            if (is_array($result) && is_numeric($result['lat'] ?? null) && is_numeric($result['lon'] ?? null)) {
                $property->update(['latitude' => $result['lat'], 'longitude' => $result['lon']]);

                return;
            }
        }

        Log::warning('No coordinates found for property address; geocoding will be retried.', ['property_id' => $this->propertyId]);
        throw new \RuntimeException('No coordinates found for property address.');
    }
}