<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\UserAccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(protected UserAccountDeletionService $userAccountDeletionService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()->with(['roles', 'company']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role if provided
        if ($role = $request->string('role')->toString()) {
            $query->whereHas('roles', function ($builder) use ($role) {
                $builder->where('name', $role);
            });
        }

        $perPage = (int) $request->input('per_page', 15);

        $users = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return AdminUserResource::collection($users);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'You cannot delete your own account from the admin panel.'
            ], 422);
        }

        $this->userAccountDeletionService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully.'
        ]);
    }
}
