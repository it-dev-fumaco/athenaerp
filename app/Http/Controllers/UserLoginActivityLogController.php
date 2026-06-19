<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListUserLoginActivityRequest;
use App\Models\UserLoginActivity;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class UserLoginActivityLogController extends Controller
{
    /**
     * Admin page (Vue app); access enforced by route middleware.
     */
    public function page(): View
    {
        return view('admin.login_activity');
    }

    /**
     * Paginated JSON for login activity (filters: user, status, date_from, date_to).
     * Dates are interpreted in the application timezone; stored values use DB session timezone.
     */
    public function index(ListUserLoginActivityRequest $request): JsonResponse
    {
        $data = $request->validated();
        $perPage = (int) ($data['per_page'] ?? 20);

        $query = UserLoginActivity::query()
            ->with(['user:wh_user,name,full_name'])
            ->orderByDesc('login_at');

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (! empty($data['user'])) {
            $needle = addcslashes(trim($data['user']), '%_\\');
            $like = '%'.$needle.'%';
            $query->where(function ($q) use ($like) {
                $q->where('username', 'like', $like)
                    ->orWhere('user_id', 'like', $like);
            });
        }

        if (! empty($data['date_from'])) {
            $from = Carbon::parse($data['date_from'])->startOfDay();
            $query->where('login_at', '>=', $from);
        }

        if (! empty($data['date_to'])) {
            $to = Carbon::parse($data['date_to'])->endOfDay();
            $query->where('login_at', '<=', $to);
        }

        return response()->json(
            $query->paginate($perPage)
        );
    }
}
