<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserAccountDeletionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['company', 'roles'])->withCount('applications');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users', [
            'users' => $users->getCollection()->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->roles->pluck('name')->values()->all(),
                'company' => $user->company ? ['name' => $user->company->name] : null,
                'applications_count' => $user->applications_count,
                'email_verified' => $user->email_verified_at !== null,
                'is_demo' => (bool) $user->is_demo,
                'is_admin' => $user->hasRole('Admin'),
                'joined_label' => $user->created_at?->translatedFormat('M d, Y'),
            ])->values()->all(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
            ],
            'labels' => [
                'title' => __('admin.user_management_title'),
                'sidebar_overview' => __('admin.sidebar_overview'),
                'sidebar_management' => __('admin.sidebar_management'),
                'sidebar_users' => __('admin.sidebar_users'),
                'sidebar_jobs' => __('admin.sidebar_jobs'),
                'admin_area' => __('admin.admin_area'),
                'registered_users_count' => __('admin.registered_users_count', ['count' => $users->total()]),
                'search' => __('admin.search'),
                'search_button' => __('admin.search_button'),
                'search_placeholder' => __('admin.search_placeholder'),
                'all_roles' => __('admin.all_roles'),
                'admin' => __('admin.admin'),
                'recruiter' => __('admin.recruiter'),
                'candidate' => __('admin.candidate'),
                'clear' => __('admin.clear'),
                'clear_button' => __('admin.clear_button'),
                'no_users_found' => __('admin.no_users_found'),
                'no_users_match' => __('admin.no_users_match'),
                'no_users_match_criteria' => __('admin.no_users_match_criteria'),
                'no_users_registered' => __('admin.no_users_registered'),
                'phone' => __('admin.phone'),
                'company' => __('admin.company'),
                'applications' => __('admin.applications'),
                'email_verified' => __('admin.email_verified'),
                'yes' => __('admin.yes'),
                'no' => __('admin.no'),
                'joined' => __('admin.joined'),
                'not_provided' => __('admin.not_provided'),
                'delete' => __('admin.delete'),
                'delete_user' => __('admin.delete_user'),
                'protected' => __('admin.protected'),
                'delete_user_confirm' => __('admin.delete_user_confirm'),
                'delete_user_confirmation' => __('admin.delete_user_confirmation'),
                'demo_account' => __('common.demo_account'),
                'loading_more' => __('common.loading_more'),
                'cancel' => __('common.cancel'),
                'show_more' => __('common.show_more'),
            ],
        ]);
    }

    public function destroy(string $locale, User $user, UserAccountDeletionService $deletionService)
    {
        if ($user->hasRole('Admin')) {
            return back()->with('error', __('admin.cannot_delete_admin'));
        }

        $deletionService->deleteUserAccount($user, false);

        return back()->with('success', __('admin.user_deleted'));
    }
}
