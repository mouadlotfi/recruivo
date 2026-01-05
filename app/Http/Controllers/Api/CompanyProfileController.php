<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyProfileController extends Controller
{
    /**
     * Get the company profile for the authenticated recruiter.
     */
    public function show()
    {
        $user = Auth::user();
        
        if (!$user->hasRole('Recruiter') || !$user->company_id) {
            return response()->json([
                'message' => 'Only recruiters can access company profiles'
            ], 403);
        }
        
        $company = Company::findOrFail($user->company_id);

        return response()->json([
            'data' => $company
        ]);
    }

    /**
     * Update the company profile.
     */
    public function update(UpdateCompanyProfileRequest $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('Recruiter') || !$user->company_id) {
            return response()->json([
                'message' => 'Only recruiters can update company profiles'
            ], 403);
        }
        
        $company = Company::findOrFail($user->company_id);
        $data = $request->validated();
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }
        
        $company->update($data);
        
        return response()->json([
            'message' => 'Company profile updated successfully',
            'data' => $company->fresh()
        ]);
    }

}
