<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\OrganizationResponsible;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TenderController extends Controller
{
    public function create(Request $request)
    {
        $user = Employee::where('username', $request->creatorUsername)->first();

        if (!$user) {
            return response(json_encode([
                'reason' => 'Пользователь не существует'
            ], JSON_UNESCAPED_UNICODE), 401)
                ->header('Content-Type', 'application/json');
        }

        $organizationResponsible = OrganizationResponsible::where('user_id', $user->id)->first();
        try {   
            $organization = Organization::find($request->organizationId);
        } catch (\Exception $e) {
            return response(json_encode([
                'reason' => 'Организаия не существует'
            ], JSON_UNESCAPED_UNICODE), 403)
            ->header('Content-Type', 'application/json');
        }
        
        if (!$organization || $organizationResponsible->organization_id !== $organization->id) {
            return response(json_encode([
                'reason' => 'Недостаточно прав для выполнения действия'
            ], JSON_UNESCAPED_UNICODE), 403)
                ->header('Content-Type', 'application/json');
        }

        $tender = Tender::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'service_type' => $request->serviceType,
            'organization_id' => $request->organizationId,
            'creator_username' => $user->username,
        ]);
        
        $tender = $tender->fresh();

        return response(json_encode([
            'id' => $tender->id,
            'name' => $tender->name,
            'description' => $tender->description,
            'status' => $tender->status,
            'serviceType' => $tender->service_type,
            'version' => $tender->version,
            'createdAt' => $tender->created_at,
        ], JSON_UNESCAPED_UNICODE), 200)
            ->header('Content-Type', 'application/json');
    }

    public function getTenders(Request $request)
    {
        try {
            $validated = $request->validate([
                'limit' => 'integer|min:1|max:100',
                'offset' => 'integer|min:0',
                'service_type' => ['sometimes', 'array'],
                'service_type.*' => [Rule::in(Tender::SERVICE_TYPES)],
            ]);

            $limit = $validated['limit'] ?? 5;
            $offset = $validated['offset'] ?? 0;
            $serviceTypes = $validated['service_type'] ?? [];

            $query = Tender::query();

            if (!empty($serviceTypes)) {
                $query->whereIn('service_type', $serviceTypes);
            }

            $query->orderBy('name', 'asc');

            $total = $query->count();

            $tenders = $query->offset($offset)->limit($limit)->get();

            $formattedTenders = $tenders->map(function ($tender) {
                return [
                    'id' => $tender->id,
                    'name' => $tender->name,
                    'description' => $tender->description,
                    'status' => $tender->status,
                    'serviceType' => $tender->service_type,
                    'version' => $tender->version,
                    'createdAt' => $tender->created_at->toIso8601String()
                ];
            });

            return response()->json($formattedTenders)
                ->header('X-Total-Count', $total)
                ->header('X-Limit', $limit)
                ->header('X-Offset', $offset);

        } catch (ValidationException $e) {
            return response()->json([
                'reason' => 'Неверный формат запроса или его параметры'
            ], 400, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'reason' => 'Произошла ошибка пи обработке запроса'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function getMyTenders(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string',
                'limit' => 'integer|min:1|max:100',
                'offset' => 'integer|min:0',
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

            $query = Tender::where('creator_username', $username);

            $query->orderBy('name', 'asc');

            $total = $query->count();

            $tenders = $query->offset($offset)->limit($limit)->get();

            $formattedTenders = $tenders->map(function ($tender) {
                return [
                    'id' => $tender->id,
                    'name' => $tender->name,
                    'description' => $tender->description,
                    'status' => $tender->status,
                    'serviceType' => $tender->service_type,
                    'version' => $tender->version,
                    'createdAt' => $tender->created_at->toIso8601String()
                ];
            });

            return response()->json($formattedTenders, 200, [], JSON_UNESCAPED_UNICODE)
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

    public function getStatus(Request $request, $tenderId)
    {
        $user = Employee::where('username', $request->username)->first();
        if (!$user) {
            return response(json_encode([
                'reason' => 'Пользователь не существует или некорректен'
            ], JSON_UNESCAPED_UNICODE), 401)
                ->header('Content-Type', 'application/json');
        }

        $tender = Tender::find($tenderId);
        if (!$tender) {
            return response(json_encode([
                'reason' => 'Тендер не найден'
            ], JSON_UNESCAPED_UNICODE), 404)
                ->header('Content-Type', 'application/json');
        }

        $organizationResponsible = OrganizationResponsible::where('user_id', $user->id)->first();
        $userOrganizationId = $organizationResponsible ? $organizationResponsible->organization_id : null;
        
        if ($tender->status === 'Published' || $userOrganizationId === $tender->organization_id) {
            return response(json_encode([
                'status' => $tender->status
            ], JSON_UNESCAPED_UNICODE), 200)
                ->header('Content-Type', 'application/json');
        }

        return response(json_encode([
            'reason' => 'Недостаточно прав для выполнения действия'
        ], JSON_UNESCAPED_UNICODE), 403)
            ->header('Content-Type', 'application/json');
    }

    public function changeStatus(Request $request, $tenderId)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', Rule::in(['Created', 'Published', 'Closed'])],
            'username' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response(json_encode([
                'reason' => 'Неверный формат запроса или его параметры'
            ], JSON_UNESCAPED_UNICODE), 400)
                ->header('Content-Type', 'application/json');
        }

        $user = Employee::where('username', $request->username)->first();
        if (!$user) {
            return response(json_encode([
                'reason' => 'Пользователь не существует или некорректен'
            ], JSON_UNESCAPED_UNICODE), 401)
                ->header('Content-Type', 'application/json');
        }

        $tender = Tender::find($tenderId);
        if (!$tender) {
            return response(json_encode([
                'reason' => 'Тендер не найден'
            ], JSON_UNESCAPED_UNICODE), 404)
                ->header('Content-Type', 'application/json');
        }

         $organizationResponsible = OrganizationResponsible::where('user_id', $user->id)->first();
         $userOrganizationId = $organizationResponsible ? $organizationResponsible->organization_id : null;
        
        if ($userOrganizationId!== $tender->organization_id) {
            return response(json_encode([
                'reason' => 'Недостаточно прав для выполнения действия'
            ], JSON_UNESCAPED_UNICODE), 403)
                ->header('Content-Type', 'application/json');
        }

        $tender->status = $request->status;
        $tender->save();

        return response(json_encode([
            'id' => $tender->id,
            'name' => $tender->name,
            'description' => $tender->description,
            'status' => $tender->status,
            'serviceType' => $tender->service_type,
            'version' => $tender->version,
            'createdAt' => $tender->created_at,
        ], JSON_UNESCAPED_UNICODE), 200)
            ->header('Content-Type', 'application/json');
    }

    public function editTender(Request $request, $tenderId)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'serviceType' => ['sometimes', Rule::in(Tender::SERVICE_TYPES)],
        ]);

        if ($validator->fails()) {
            return response(json_encode([
                'reason' => 'Неверный формат запроса или его параметры'
            ], JSON_UNESCAPED_UNICODE), 400)
                ->header('Content-Type', 'application/json');
        }

        $user = Employee::where('username', $request->username)->first();
        if (!$user) {
            return response(json_encode([
                'reason' => 'Пользователь не существует или некорректен'
            ], JSON_UNESCAPED_UNICODE), 401)
                ->header('Content-Type', 'application/json');
        }

        $tender = Tender::find($tenderId);
        if (!$tender) {
            return response(json_encode([
                'reason' => 'Тендер не найден'
            ], JSON_UNESCAPED_UNICODE), 404)
                ->header('Content-Type', 'application/json');
        }

        $organizationResponsible = OrganizationResponsible::where('user_id', $user->id)->first();
        $userOrganizationId = $organizationResponsible ? $organizationResponsible->organization_id : null;
        
        if ($userOrganizationId !== $tender->organization_id) {
            return response(json_encode([
                'reason' => 'Недостаточно прав для выполнения действия'
            ], JSON_UNESCAPED_UNICODE), 403)
                ->header('Content-Type', 'application/json');
        }

        if ($request->has('name')) {
            $tender->name = $request->name;
        }
        if ($request->has('description')) {
            $tender->description = $request->description;
        }
        if ($request->has('serviceType')) {
            $tender->service_type = $request->serviceType;
        }

        $tender->save();
        $tender->refresh();

        return response(json_encode([
            'id' => $tender->id,
            'name' => $tender->name,
            'description' => $tender->description,
            'status' => $tender->status,
            'serviceType' => $tender->service_type,
            'version' => $tender->version,
            'createdAt' => $tender->created_at,
        ], JSON_UNESCAPED_UNICODE), 200)
            ->header('Content-Type', 'application/json');
    }

    public function rollbackTender(Request $request, $tenderId, $version)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response(json_encode([
                'reason' => 'Неверный формат запроса или его параметры'
            ], JSON_UNESCAPED_UNICODE), 400)
                ->header('Content-Type', 'application/json');
        }

        $user = Employee::where('username', $request->username)->first();
        if (!$user) {
            return response(json_encode([
                'reason' => 'Пользователь не существует или некорректен'
            ], JSON_UNESCAPED_UNICODE), 401)
                ->header('Content-Type', 'application/json');
        }

        $query = '
            WITH previous_version AS (
                SELECT name, description, status, service_type, version
                FROM tender_versions
                WHERE tender_id = ? AND version = ?
            )
            UPDATE tenders t
            SET name = pv.name,
                description = pv.description,
                status = pv.status,
                service_type = pv.service_type,
                version = pv.version
            FROM previous_version pv
            WHERE t.id = ?
            RETURNING t.id, t.name, t.description, t.status, t.service_type, t.organization_id, t.creator_username, t.version, t.created_at
        ';

        try {
            $result = DB::selectOne($query, [$tenderId, $version, $tenderId]);

            if (!$result) {
                return response(json_encode([
                    'reason' => 'Тендер или версия не найдены'
                ], JSON_UNESCAPED_UNICODE), 404)
                    ->header('Content-Type', 'application/json');
            }

            return response(json_encode([
                'id' => $result->id,
                'name' => $result->name,
                'description' => $result->description,
                'status' => $result->status,
                'serviceType' => $result->service_type,
                'version' => $result->version,
                'createdAt' => $result->created_at,
            ], JSON_UNESCAPED_UNICODE), 200)
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return response(json_encode([
                'reason' => 'Не удалось выполнить откат тендера: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE), 500)
                ->header('Content-Type', 'application/json');
        }
    }
}
    

