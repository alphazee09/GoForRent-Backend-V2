<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EquipmentCategoryController extends Controller
{
    public function __construct()
    {
        // Protect all methods except index and show for public viewing
        $this->middleware("auth:api")->except(["index", "show"]);
        // Apply specific permissions for admin actions
        $this->middleware("permission:manage_equipment_categories|create_equipment_categories", ["only" => ["store"]]);
        $this->middleware("permission:manage_equipment_categories|edit_equipment_categories", ["only" => ["update"]]);
        $this->middleware("permission:manage_equipment_categories|delete_equipment_categories", ["only" => ["destroy"]]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = EquipmentCategory::query();
        // Basic filtering example (can be expanded)
        if ($request->has("is_active")) {
            // Assuming an is_active field, though not in current schema, for future use or if added
            // $categories->where('is_active', $request->is_active);
        }
        return response()->json($categories->with("translations")->paginate($request->per_page ?? 15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name_en" => "required|string|max:255",
            "name_ar" => "required|string|max:255",
            // Add other validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = new EquipmentCategory();
        $category->setTranslation("name", "en", $request->name_en);
        $category->setTranslation("name", "ar", $request->name_ar);
        // Set other fields if any
        $category->save();

        return response()->json([
            "message" => "Equipment category created successfully.",
            "category" => $category->load("translations")
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $category = EquipmentCategory::with("translations")->find($id);

        if (!$category) {
            return response()->json(["message" => "Equipment category not found."], 404);
        }

        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $category = EquipmentCategory::find($id);

        if (!$category) {
            return response()->json(["message" => "Equipment category not found."], 404);
        }

        $validator = Validator::make($request->all(), [
            "name_en" => "sometimes|required|string|max:255",
            "name_ar" => "sometimes|required|string|max:255",
            // Add other validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has("name_en")) {
            $category->setTranslation("name", "en", $request->name_en);
        }
        if ($request->has("name_ar")) {
            $category->setTranslation("name", "ar", $request->name_ar);
        }
        // Update other fields if any
        $category->save();

        return response()->json([
            "message" => "Equipment category updated successfully.",
            "category" => $category->load("translations")
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = EquipmentCategory::find($id);

        if (!$category) {
            return response()->json(["message" => "Equipment category not found."], 404);
        }

        // Consider related equipment before deleting, or use onDelete cascade if appropriate
        if ($category->equipment()->count() > 0) {
            return response()->json(["message" => "Cannot delete category with associated equipment."], 400);
        }

        $category->delete();

        return response()->json(["message" => "Equipment category deleted successfully."]);
    }
}

