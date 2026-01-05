<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyLogoController extends Controller
{
    /**
     * Serve company logo by company slug.
     */
    public function show(Request $request, string $slug)
    {
        $company = Company::where('slug', $slug)->first();
        
        if (!$company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }
        
        if (!$company->logo_path) {
            return response()->json([
                'message' => 'No logo found for this company'
            ], 404);
        }
        
        if (!Storage::disk('public')->exists($company->logo_path)) {
            return response()->json([
                'message' => 'Logo file not found'
            ], 404);
        }
        
        return Storage::disk('public')->response($company->logo_path);
    }
}
