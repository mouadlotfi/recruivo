<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roles = $this->roles->pluck('name')->values();
        $primaryRole = $roles->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles,
            'account_type' => $primaryRole,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'slug' => $this->company->slug,
                    'logo_url' => $this->company->logo_url,
                ];
            }),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
