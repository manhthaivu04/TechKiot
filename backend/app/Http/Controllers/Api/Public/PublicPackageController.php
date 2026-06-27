<?php

namespace App\Http\Controllers\Api\Public;

use App\Contracts\Services\PackageServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicPackageController extends Controller
{
    public function __construct(
        private readonly PackageServiceInterface $service,
    ) {}

    /** GET /api/public/packages */
    public function index(Request $request): AnonymousResourceCollection
    {
        $packages = $this->service->allActiveWithFeatures();

        return PackageResource::collection($packages);
    }
}
