<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Staf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SSOTController extends Controller
{
    /**
     * Get staff information by IC Number (SSOT - Single Source of Truth)
     * 
     * @param string $noKp
     * @return JsonResponse
     */
    public function getStafByNoKp(string $noKp): JsonResponse
    {
        try {
            // Remove any non-numeric characters from IC
            $noKp = preg_replace('/[^0-9]/', '', $noKp);

            // Validate IC format (must be 12 digits)
            if (strlen($noKp) != 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'No. Kad Pengenalan tidak sah. Mesti 12 digit.',
                    'message_en' => 'Invalid Identity Card Number. Must be 12 digits.',
                ], 400);
            }

            // Find staff with relationships
            $staf = Staf::with(['jawatan', 'gred', 'bahagian', 'status'])
                ->where('no_kp', $noKp)
                ->first();

            if (!$staf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staf tidak dijumpai.',
                    'message_en' => 'Staff not found.',
                ], 404);
            }

            // Return formatted data
            return response()->json([
                'success' => true,
                'data' => [
                    'id_staf' => $staf->id_staf,
                    'no_staf' => $staf->no_staf,
                    'no_kp' => $staf->no_kp,
                    'nama' => $staf->nama,
                    'emel' => $staf->emel,
                    'telefon' => $staf->telefon,
                    'gambar_url' => $staf->gambar_url,
                    'jawatan' => $staf->jawatan ? [
                        'id_jawatan' => $staf->jawatan->id_jawatan,
                        'jawatan' => $staf->jawatan->jawatan,
                        'skim' => $staf->jawatan->skim,
                    ] : null,
                    'gred' => $staf->gred ? [
                        'id_gred' => $staf->gred->id_gred,
                        'gred' => $staf->gred->gred,
                    ] : null,
                    'bahagian' => $staf->bahagian ? [
                        'id_bahagian' => $staf->bahagian->id_bahagian,
                        'bahagian' => $staf->bahagian->bahagian,
                    ] : null,
                    'status' => $staf->status ? [
                        'id_status' => $staf->status->id_status,
                        'status' => $staf->status->status,
                    ] : null,
                    'is_active' => $staf->isActive(),
                ],
                'message' => 'Data staf berjaya diambil.',
                'message_en' => 'Staff data retrieved successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat sistem: ' . $e->getMessage(),
                'message_en' => 'System error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search staff by name or staff number
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchStaf(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q');
            
            if (empty($query) || strlen($query) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carian terlalu pendek. Minimum 3 aksara.',
                    'message_en' => 'Search query too short. Minimum 3 characters.',
                ], 400);
            }

            $results = Staf::with(['jawatan', 'gred', 'bahagian', 'status'])
                ->where(function($q) use ($query) {
                    $q->where('nama', 'like', "%{$query}%")
                      ->orWhere('no_staf', 'like', "%{$query}%")
                      ->orWhere('no_kp', 'like', "%{$query}%")
                      ->orWhere('emel', 'like', "%{$query}%");
                })
                ->where('id_status', 1) // Only active staff
                ->limit(20)
                ->get()
                ->map(function($staf) {
                    return [
                        'id_staf' => $staf->id_staf,
                        'no_staf' => $staf->no_staf,
                        'no_kp' => $staf->no_kp,
                        'nama' => $staf->nama,
                        'emel' => $staf->emel,
                        'telefon' => $staf->telefon,
                        'gambar_url' => $staf->gambar_url,
                        'jawatan' => $staf->jawatan?->jawatan,
                        'gred' => $staf->gred?->gred,
                        'bahagian' => $staf->bahagian?->bahagian,
                        'status' => $staf->status?->status,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => $results->count(),
                'message' => 'Carian selesai.',
                'message_en' => 'Search completed.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat sistem: ' . $e->getMessage(),
                'message_en' => 'System error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all active staff (paginated)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllStaf(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 50);
            $bahagianId = $request->input('bahagian');

            $query = Staf::with(['jawatan', 'gred', 'bahagian', 'status'])
                ->where('id_status', 1); // Only active staff

            if ($bahagianId) {
                $query->where('id_bahagian', $bahagianId);
            }

            $staf = $query->orderBy('nama', 'asc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $staf->map(function($s) {
                    return [
                        'id_staf' => $s->id_staf,
                        'no_staf' => $s->no_staf,
                        'no_kp' => $s->no_kp,
                        'nama' => $s->nama,
                        'emel' => $s->emel,
                        'telefon' => $s->telefon,
                        'gambar_url' => $s->gambar_url,
                        'jawatan' => $s->jawatan?->jawatan,
                        'gred' => $s->gred?->gred,
                        'bahagian' => $s->bahagian?->bahagian,
                    ];
                }),
                'pagination' => [
                    'total' => $staf->total(),
                    'per_page' => $staf->perPage(),
                    'current_page' => $staf->currentPage(),
                    'last_page' => $staf->lastPage(),
                    'from' => $staf->firstItem(),
                    'to' => $staf->lastItem(),
                ],
                'message' => 'Data staf berjaya diambil.',
                'message_en' => 'Staff data retrieved successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ralat sistem: ' . $e->getMessage(),
                'message_en' => 'System error: ' . $e->getMessage(),
            ], 500);
        }
    }
}

