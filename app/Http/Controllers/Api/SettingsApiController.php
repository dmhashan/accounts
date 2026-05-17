<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsApiController extends Controller
{
    public function __construct(
        private readonly MediaStorageService $media,
    ) {}

    public function general(): JsonResponse
    {
        $tenant = app('tenant');

        return response()->json([
            'data' => [
                'name'       => $tenant->name,
                'address'    => $tenant->address,
                'email'      => $tenant->email,
                'phone'      => $tenant->phone,
                'logo_url'   => $tenant->logo_path ? $this->media->url($tenant->logo_path) : null,
            ],
        ]);
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'email'   => ['nullable', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:50'],
        ]);

        $tenant = app('tenant');

        $tenant->update([
            'name'    => $request->input('name'),
            'address' => $request->input('address'),
            'email'   => $request->input('email'),
            'phone'   => $request->input('phone'),
        ]);

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => [
                'name'       => $tenant->name,
                'address'    => $tenant->address,
                'email'      => $tenant->email,
                'phone'      => $tenant->phone,
                'logo_url'   => $tenant->logo_path ? $this->media->url($tenant->logo_path) : null,
            ],
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp,svg'],
        ]);

        $tenant = app('tenant');

        if ($tenant->logo_path) {
            $this->media->delete($tenant->logo_path);
        }

        $path = $this->media->store($request->file('logo'), 'tenant-logos');

        $tenant->update(['logo_path' => $path]);

        return response()->json([
            'message'  => 'Logo uploaded successfully.',
            'logo_url' => $this->media->url($path),
        ]);
    }

    public function deleteLogo(): JsonResponse
    {
        $tenant = app('tenant');

        if ($tenant->logo_path) {
            $this->media->delete($tenant->logo_path);
            $tenant->update(['logo_path' => null]);
        }

        return response()->json(['message' => 'Logo removed successfully.']);
    }
}
