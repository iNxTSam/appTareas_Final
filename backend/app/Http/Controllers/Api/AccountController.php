<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $account = $request->user();
        $teamIds = $account->teams()->pluck('team.team_id');

        $accounts = Account::query()
            ->where(function (Builder $query) use ($account, $teamIds): void {
                $query->whereHas('teams', static fn (Builder $query): Builder => $query
                    ->whereIn('team.team_id', $teamIds))
                    ->orWhere('account_id', $account->account_id);
            })
            ->orderBy('name')
            ->get(['account_id', 'name', 'email']);

        return response()->json([
            'data' => $accounts->map(static fn (Account $item): array => [
                'id' => $item->account_id,
                'name' => $item->name,
                'email' => $item->email,
            ])->values(),
        ]);
    }
}
