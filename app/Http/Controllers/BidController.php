<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\OrganizationResponsible;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BidController extends Controller
{
    public function createBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'tenderId' => 'required|uuid',
            'authorType' => 'required|in:Organization,User',
            'authorId' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'reason' => 'Неверный формат запроса или его параметры'
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $author = null;
        if ($request->authorType === 'User') {
            $author = Employee::where('id', $request->authorId)->first();
        } else {
            $author = Organization::where('id', $request->authorId)->first();
        }

        if (!$author) {
            return response()->json([
                'reason' => 'Пользователь не существует или некорректен'
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }

        $tender = Tender::find($request->tenderId);
        if (!$tender) {
            return response()->json([
                'reason' => 'Тендер не найден'
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        if ($request->authorType === 'Organization') {
            if ($author->id !== $tender->organization_id) {
                return response()->json([
                    'reason' => 'Недостаточно прав для выполнения действия'
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }
        } elseif ($request->authorType === 'User') {
            $organizationResponsible = OrganizationResponsible::where('user_id', $author->id)->first();
            if (!$organizationResponsible || $organizationResponsible->organization_id !== $tender->organization_id) {
                return response()->json([
                    'reason' => 'Недостаточно прав для выполнения действия'
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }
        }

        $bid = Bid::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'description' => $request->description,
            'tender_id' => $request->tenderId,
            'author_type' => $request->authorType,
            'author_id' => $request->authorId,
            'status' => 'Created',
        ]);

        $bid->refresh();
        return response()->json([
            'id' => $bid->id,
            'name' => $bid->name,
            'description' => $bid->description,
            'status' => $bid->status,
            'authorType' => $bid->author_type,
            'authorId' => $bid->author_id,
            'version' => $bid->version,
            'createdAt' => $bid->created_at->toIso8601String(),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getMyBids(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string',
                'limit' => 'integer',
                'offset' => 'integer',
            ]);

            $username = $validated['username'];
            $limit = $validated['limit'] ?? 5;
            $offset = $validated['offset'] ?? 0;

            $user = Employee::where('username', $username)->first();

            if (!$user) {
                return response()->json([
                    'reason' => 'Пользователь не существует или некорректен.'
                ], 401, [], JSON_UNESCAPED_UNICODE);
            }

            $query = Bid::where('author_id', $user->id)->where('author_type', 'User');

            $query->orderBy('name', 'asc');

            $total = $query->count();

            $bids = $query->offset($offset)->limit($limit)->get();

            $formattedBids = $bids->map(function ($bid) {
                return [
                    'id' => $bid->id,
                    'name' => $bid->name,
                    'status' => $bid->status,
                    'authorType' => $bid->author_type,
                    'authorId' => $bid->author_id,
                    'version' => $bid->version,
                    'createdAt' => $bid->created_at->toIso8601String()
                ];
            });

            return response()->json($formattedBids, 200, [], JSON_UNESCAPED_UNICODE)
                ->header('X-Total-Count', $total)
                ->header('X-Limit', $limit)
                ->header('X-Offset', $offset);

        } catch (ValidationException $e) {
            return response()->json([
                'reason' => 'Неверный формат запроса или его параметры',
            ], 400, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'reason' => 'Произошла ошибка при обработке запроса'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'tenderId' => 'required|uuid',
            'authorType' => 'required|in:Organization,User',
            'authorId' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'reason' => 'Неверный формат запроса или его параметры'
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $tender = Tender::find($request->tenderId);
        if (!$tender) {
            return response()->json([
                'reason' => 'Тендер не найден'
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        if ($request->authorType === 'User') {
            $author = Employee::find($request->authorId);
            $authorType = 'User';
        } else {
            $author = Organization::find($request->authorId);
            $authorType = 'Organization';
        }

        if (!$author) {
            return response()->json([
                'reason' => ($request->authorType === 'User' ? 'Пользователь' : 'Организация') . ' не существует или некорректен'
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }

        $bid = new Bid([
            'id' => Str::uuid(),
            'name' => $request->name,
            'description' => $request->description,
            'tender_id' => $request->tenderId,
            'status' => 'Created',
            'author_id' => $author->id,
            'author_type' => $authorType,
        ]);

        $bid->save();

        return response()->json([
            'id' => $bid->id,
            'name' => $bid->name,
            'status' => $bid->status,
            'authorType' => $authorType,
            'authorId' => $bid->author_id,
            'version' => $bid->version,
            'createdAt' => $bid->created_at->toIso8601String(),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
