<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class EquipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api")->except(["index", "show"]);
        // Permissions for general equipment management (admin)
        $this->middleware("permission:manage_equipment|create_equipment", ["only" => ["store"]]);
        $this->middleware("permission:manage_equipment|edit_equipment", ["only" => ["update"]]);
        $this->middleware("permission:manage_equipment|delete_equipment", ["only" => ["destroy"]]);
        
        // Permissions for owners managing their own equipment
        // These will be checked within the methods for specific ownership
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Equipment::query()->with(["translations", "category.translations"]);

        // Basic filtering (can be expanded significantly)
        if ($request->has("category_id")) {
            $query->where("equipment_category_id", $request->category_id);
        }
        if ($request->has("status")) {
            $query->where("status", $request->status);
        }
        if ($request->has("search")) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereTranslationLike("name", "%{$searchTerm}%")
                  ->orWhereTranslationLike("description", "%{$searchTerm}%")
                  ->orWhere("barcode_value", "LIKE", "%{$searchTerm}%");
            });
        }

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            "equipment_category_id" => "required|exists:equipment_categories,id",
            "name_en" => "required|string|max:255",
            "name_ar" => "required|string|max:255",
            "description_en" => "required|string",
            "description_ar" => "required|string",
            "barcode_value" => "nullable|string|max:255|unique:equipment,barcode_value",
            "images" => "nullable|array",
            "images.*" => "image|mimes:jpeg,png,jpg,gif,svg|max:2048", // Validate each image
            "min_rental_period_hours" => "required|integer|min:1",
            "max_rental_period_hours" => "required|integer|gte:min_rental_period_hours",
            "rewards_points_acceptable" => "required|boolean",
            "status" => "required|in:available,rented,maintenance,unavailable",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->except(["name_en", "name_ar", "description_en", "description_ar", "images"]);
        
        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile("images")) {
            foreach ($request->file("images") as $imageFile) {
                $path = $imageFile->store("equipment_images", "public");
                $imagePaths[] = Storage::url($path);
            }
        }
        $data["images"] = $imagePaths; // Store as JSON array of URLs

        // Add owner_id if the user creating is an Owner (or Admin acting as one)
        // This logic might need refinement based on how owners are managed
        if ($user->hasRole("Owner")) {
            $data["owner_id"] = $user->id;
        } else if ($user->hasRole("Admin") && $request->has("owner_id")) {
            // Admin can specify owner_id if provided
            $data["owner_id"] = $request->owner_id;
        } else {
            // Default or error if not an owner and no owner_id provided by admin
            // For now, let's assume admin must provide owner_id if not an owner themselves
            // Or, if equipment can be system-owned, handle that case.
            // This part needs clarification based on business rules for equipment ownership.
            // For now, we'll allow it to be null if not set, assuming a migration default or nullable field.
        }

        $equipment = new Equipment($data);
        $equipment->setTranslation("name", "en", $request->name_en);
        $equipment->setTranslation("name", "ar", $request->name_ar);
        $equipment->setTranslation("description", "en", $request->description_en);
        $equipment->setTranslation("description", "ar", $request->description_ar);
        $equipment->save();

        return response()->json([
            "message" => "Equipment created successfully.",
            "equipment" => $equipment->load(["translations", "category.translations"])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $equipment = Equipment::with(["translations", "category.translations"])->find($id);

        if (!$equipment) {
            return response()->json(["message" => "Equipment not found."], 404);
        }

        return response()->json($equipment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $equipment = Equipment::find($id);
        $user = Auth::user();

        if (!$equipment) {
            return response()->json(["message" => "Equipment not found."], 404);
        }

        // Authorization: Admin can edit any, Owner can edit their own
        if (!$user->hasRole("Admin") && ($user->hasRole("Owner") && $equipment->owner_id !== $user->id)) {
             if (!$user->can("edit_own_equipment") || $equipment->owner_id !== $user->id) {
                 return response()->json(["message" => "Unauthorized to update this equipment."], 403);
             }
        }
        
        $validator = Validator::make($request->all(), [
            "equipment_category_id" => "sometimes|required|exists:equipment_categories,id",
            "name_en" => "sometimes|required|string|max:255",
            "name_ar" => "sometimes|required|string|max:255",
            "description_en" => "sometimes|required|string",
            "description_ar" => "sometimes|required|string",
            "barcode_value" => "sometimes|nullable|string|max:255|unique:equipment,barcode_value," . $equipment->id,
            "images" => "nullable|array",
            "images.*" => "image|mimes:jpeg,png,jpg,gif,svg|max:2048",
            "min_rental_period_hours" => "sometimes|required|integer|min:1",
            "max_rental_period_hours" => "sometimes|required|integer|gte:min_rental_period_hours",
            "rewards_points_acceptable" => "sometimes|required|boolean",
            "status" => "sometimes|required|in:available,rented,maintenance,unavailable",
            "owner_id" => "sometimes|nullable|exists:users,id" // Admin might change owner
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->except(["name_en", "name_ar", "description_en", "description_ar", "images", "_method"]);

        // Handle image uploads (replace or add)
        if ($request->hasFile("images")) {
            // Optionally delete old images first if replacing all
            // foreach (json_decode($equipment->images, true) as $oldImagePath) {
            //     Storage::disk("public")->delete(str_replace("/storage/", "", $oldImagePath));
            // }
            $imagePaths = [];
            foreach ($request->file("images") as $imageFile) {
                $path = $imageFile->store("equipment_images", "public");
                $imagePaths[] = Storage::url($path);
            }
            $data["images"] = $imagePaths;
        } else if ($request->exists("images") && is_array($request->images)){
            // If images are sent as an array of existing URLs (e.g. to reorder or remove some)
            $data["images"] = $request->images;
        }

        // Prevent non-admins from changing owner_id unless it's to themselves (if that's a rule)
        if (!$user->hasRole("Admin") && $request->has("owner_id") && $request->owner_id !== $equipment->owner_id) {
            unset($data["owner_id"]); // Or return error
        }

        $equipment->fill($data);

        if ($request->has("name_en")) {
            $equipment->setTranslation("name", "en", $request->name_en);
        }
        if ($request->has("name_ar")) {
            $equipment->setTranslation("name", "ar", $request->name_ar);
        }
        if ($request->has("description_en")) {
            $equipment->setTranslation("description", "en", $request->description_en);
        }
        if ($request->has("description_ar")) {
            $equipment->setTranslation("description", "ar", $request->description_ar);
        }
        
        $equipment->save();

        return response()->json([
            "message" => "Equipment updated successfully.",
            "equipment" => $equipment->load(["translations", "category.translations"])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $equipment = Equipment::find($id);
        $user = Auth::user();

        if (!$equipment) {
            return response()->json(["message" => "Equipment not found."], 404);
        }

        // Authorization: Admin can delete any, Owner can delete their own
        if (!$user->hasRole("Admin") && ($user->hasRole("Owner") && $equipment->owner_id !== $user->id)) {
            if (!$user->can("delete_own_equipment") || $equipment->owner_id !== $user->id) {
                return response()->json(["message" => "Unauthorized to delete this equipment."], 403);
            }
        }

        // Add checks for active rentals before deleting
        if ($equipment->rentals()->whereNotIn("status", ["completed", "cancelled"])->exists()) {
            return response()->json(["message" => "Cannot delete equipment with active rentals."], 400);
        }

        // Delete associated images from storage
        if (is_array($equipment->images)) {
            foreach ($equipment->images as $oldImagePath) {
                 if (str_starts_with($oldImagePath, Storage::url(""))) { // Check if it's a local storage URL
                    Storage::disk("public")->delete(str_replace(Storage::url(""), "", $oldImagePath));
                 }
            }
        }

        $equipment->delete();

        return response()->json(["message" => "Equipment deleted successfully."]);
    }
}

