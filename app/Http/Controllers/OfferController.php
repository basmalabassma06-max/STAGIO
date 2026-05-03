<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offer;
use Illuminate\Support\Facades\Log;

class OfferController extends Controller
{
    // 🔐 Helper
  private function getCompany()
{
    $user = auth()->user();

    if (!$user || $user->role !== 'company') {
        return null;
    }

    return \App\Models\Company::where('user_id', $user->id)->first();
}

    // 🌍 Get all active offers (Student)
    public function index(Request $request)
    {
        $query = Offer::where('is_active', true)
            ->with([
                'company:id,name,location',
                'skills:id,name'
            ]);

       if ($request->filled('location')) {
    $query->where('location', 'like', "%{$request->location}%");
}

if ($request->filled('type')) {
    $query->where('type', $request->type);
}

if ($request->filled('search')) {
    $query->where('title', 'like', '%' . $request->search . '%');
}

if ($request->filled('wilaya')) {
    $query->where('wilaya', $request->wilaya);
}
        if ($request->filled('skill_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('skills.id', $request->skill_id);
            });
        }

     

        // Only show offers that haven't passed their deadline
        $query->where(function ($q) {
            $q->whereNull('deadline')
              ->orWhere('deadline', '>', now());
        });

        return response()->json(
            $query->latest()->paginate($request->per_page ?? 10)
        );
    }

    // 📋 Company's own offers (dashboard)
  public function myOffers(Request $request)
{
    $company = $this->getCompany();

    if (!$company) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $query = Offer::where('company_id', $company->id)
        ->with('skills:id,name')
        ->withCount('applications');

    if ($request->filled('search')) {
        $query->where('title', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('type')) {
        $query->where('type', $request->type);
    }

    return response()->json(
        $query->latest()->paginate($request->per_page ?? 10)
    );
}
    // 🔍 Show single offer — company scoped (for edit form) 🔥 NEW
    public function showMine($id)
    {
        $company = $this->getCompany();

        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $offer = $company->offers()
            ->with('skills:id,name')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $offer
        ]);
    }

    // 🔍 Show single offer — public (student view)
    public function show($id)
    {
        $offer = Offer::with([
            'company:id,name,location',
            'skills:id,name'
        ])->findOrFail($id);

        return response()->json($offer);
    }

    // ➕ Create offer
    public function store(Request $request)
    {
        $company = $this->getCompany();

        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'location'    => 'required|string|max:100',
            'type'        => 'required|string|max:50',
            'deadline'    => 'nullable|date',
            'skills'      => 'nullable|array',
            'skills.*'    => 'integer|exists:skills,id',
        ]);

        try {
            $offer = Offer::create([
                'company_id'  => $company->id,
                'title'       => $request->title,
                'description' => $request->description,
                'location'    => $request->location,
                'type'        => $request->type,
                'deadline'    => $request->deadline,
                'is_active'   => true,
            ]);

            if ($request->filled('skills')) {
                $offer->skills()->sync($request->skills);
            }

            return response()->json([
                'message' => 'Offer created',
                'data'    => $offer->load('skills')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Offer create failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to create offer'], 500);
        }
    }

    // ✏️ Update offer
    public function update(Request $request, $id)
    {
        $company = $this->getCompany();

        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $offer = $company->offers()->findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'location'    => 'required|string|max:100',
            'type'        => 'required|string|max:50',
            'deadline'    => 'nullable|date',
            'skills'      => 'nullable|array',
            'skills.*'    => 'integer|exists:skills,id',
        ]);

        try {
            $offer->update($request->only([
                'title', 'description', 'location', 'type', 'deadline'
            ]));

            if ($request->has('skills')) {
                $offer->skills()->sync($request->skills ?? []);
            }

            return response()->json([
                'message' => 'Offer updated',
                'data'    => $offer->load('skills')
            ]);

        } catch (\Exception $e) {
            Log::error('Offer update failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to update offer'], 500);
        }
    }

    // ❌ Delete offer
    public function destroy($id)
    {
        $company = $this->getCompany();

        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $offer = $company->offers()->findOrFail($id);
        $offer->delete();

        return response()->json(['message' => 'Offer deleted successfully']);
    }

    // 🔁 Toggle active/inactive
    public function toggle($id)
    {
        $company = $this->getCompany();

        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $offer = $company->offers()->findOrFail($id);

        $offer->update(['is_active' => !$offer->is_active]);

        return response()->json([
            'message'   => 'Offer status updated',
            'is_active' => $offer->is_active
        ]);
    }
}