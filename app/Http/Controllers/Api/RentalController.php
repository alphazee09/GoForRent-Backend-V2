<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RentalController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api");
        // General permissions for admins
        $this->middleware("permission:manage_rentals", ["only" => ["indexAdmin", "updateStatusAdmin"]]); 
        // Permissions for users creating their own rentals
        $this->middleware("permission:create_own_rentals", ["only" => ["store"]]);
        // Users viewing their own rentals, owners viewing rentals of their equipment
        // More specific checks will be done in methods like show, indexUser, indexOwner
    }

    /**
     * Display a listing of rentals for the authenticated user (customer).
     */
    public function indexUser(Request $request)
    {
        $user = Auth::user();
        $rentals = Rental::where("user_id", $user->id)
            ->with(["equipment.translations", "equipment.category.translations", "contract"])
            ->orderBy("created_at", "desc")
            ->paginate($request->per_page ?? 15);
        return response()->json($rentals);
    }

    /**
     * Display a listing of rentals for the authenticated user (owner of equipment).
     */
    public function indexOwner(Request $request)
    {
        $user = Auth::user();
        // Get equipment IDs owned by the user
        $ownedEquipmentIds = Equipment::where("owner_id", $user->id)->pluck("id");
        
        $rentals = Rental::whereIn("equipment_id", $ownedEquipmentIds)
            ->with(["equipment.translations", "equipment.category.translations", "user", "contract"])
            ->orderBy("created_at", "desc")
            ->paginate($request->per_page ?? 15);
        return response()->json($rentals);
    }
    
    /**
     * Display a listing of all rentals (for Admin).
     */
    public function indexAdmin(Request $request)
    {
        $query = Rental::query()->with(["equipment.translations", "equipment.category.translations", "user", "contract"]);

        if ($request->has("status")) {
            $query->where("status", $request->status);
        }
        if ($request->has("user_id")) {
            $query->where("user_id", $request->user_id);
        }
        if ($request->has("equipment_id")) {
            $query->where("equipment_id", $request->equipment_id);
        }
        
        $rentals = $query->orderBy("created_at", "desc")->paginate($request->per_page ?? 15);
        return response()->json($rentals);
    }


    /**
     * Store a newly created rental resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            "equipment_id" => "required|exists:equipment,id",
            "start_datetime" => "required|date|after_or_equal:now",
            "end_datetime" => "required|date|after:start_datetime",
            "delivery_address" => "nullable|string|max:500",
            "pickup_address" => "nullable|string|max:500",
            "use_reward_points" => "boolean",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $equipment = Equipment::find($request->equipment_id);
        if (!$equipment || $equipment->status !== "available") {
            return response()->json(["message" => "Equipment is not available for rent."], 400);
        }

        $startDateTime = Carbon::parse($request->start_datetime);
        $endDateTime = Carbon::parse($request->end_datetime);
        $rentalDurationHours = $endDateTime->diffInHours($startDateTime);

        if ($rentalDurationHours < $equipment->min_rental_period_hours || $rentalDurationHours > $equipment->max_rental_period_hours) {
            return response()->json(["message" => "Rental duration is outside the allowed range for this equipment."], 400);
        }

        // Basic price calculation (placeholder - needs actual pricing logic)
        // This should come from equipment rates or a pricing service
        $pricePerHour = 10; // Example price
        $totalPrice = $rentalDurationHours * $pricePerHour;
        $amountToPay = $totalPrice;

        // Reward points logic (placeholder)
        if ($request->use_reward_points && $equipment->rewards_points_acceptable) {
            // Deduct points, adjust amountToPay - complex logic needed here
        }

        $rental = Rental::create([
            "user_id" => $user->id,
            "equipment_id" => $request->equipment_id,
            "start_datetime" => $startDateTime,
            "end_datetime" => $endDateTime,
            "total_price" => $totalPrice,
            "status" => "pending_approval", // Initial status
            "delivery_address" => $request->delivery_address,
            "pickup_address" => $request->pickup_address,
            "payment_status" => "pending",
            // other fields like discount, final_price, etc.
        ]);

        // Placeholder: Create a basic contract associated with this rental
        // Contract creation logic will be more complex
        // $contract = Contract::create([...]);
        // $rental->contract_id = $contract->id; 
        // $rental->save();

        return response()->json([
            "message" => "Rental request submitted successfully. Awaiting approval.",
            "rental" => $rental->load(["equipment.translations", "user"])
        ], 201);
    }

    /**
     * Display the specified rental resource.
     */
    public function show($id)
    {
        $user = Auth::user();
        $rental = Rental::with(["equipment.translations", "equipment.category.translations", "user", "contract"])->find($id);

        if (!$rental) {
            return response()->json(["message" => "Rental not found."], 404);
        }

        // Authorization: User can see their own, Owner can see rentals of their equipment, Admin can see all
        $isOwner = $rental->equipment->owner_id === $user->id;
        if ($rental->user_id !== $user->id && !$isOwner && !$user->hasRole("Admin")) {
            if (!($isOwner && $user->can("view_rentals")) && !($rental->user_id === $user->id && $user->can("view_own_rentals"))) {
                 return response()->json(["message" => "Unauthorized to view this rental."], 403);
            }
        }

        return response()->json($rental);
    }

    /**
     * Update the status of a rental (Admin action primarily, or specific user/owner actions like cancellation).
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $rental = Rental::find($id);

        if (!$rental) {
            return response()->json(["message" => "Rental not found."], 404);
        }

        $validator = Validator::make($request->all(), [
            "status" => "required|in:pending_approval,approved,rejected,active,completed,cancelled,pending_payment,payment_failed",
            // Add other fields that might be updated, e.g., admin_notes
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $newStatus = $request->status;
        $oldStatus = $rental->status;

        // Authorization logic for status changes
        // Example: User can cancel if status is pending_approval or approved (before active)
        if ($newStatus === "cancelled" && $rental->user_id === $user->id && in_array($oldStatus, ["pending_approval", "approved"])) {
            if (!$user->can("cancel_rentals")) {
                 return response()->json(["message" => "You do not have permission to cancel rentals."], 403);
            }
            // Proceed with cancellation by user
        } 
        // Example: Admin or Owner (for their equipment) can approve/reject
        else if (in_array($newStatus, ["approved", "rejected"]) && ($user->hasRole("Admin") || ($rental->equipment->owner_id === $user->id && $user->can("manage_rentals")))) {
            // Proceed with approval/rejection by admin/owner
             if ($newStatus === "approved") {
                // Potentially trigger contract creation, payment process initiation etc.
                $rental->equipment()->update(["status" => "rented"]); // Mark equipment as rented
            } else if ($newStatus === "rejected" || $newStatus === "cancelled") {
                // If equipment was marked rented, make it available again if no other active rental
                if ($rental->equipment->status === "rented" && !$rental->equipment->rentals()->where("status", "active")->exists()) {
                    $rental->equipment()->update(["status" => "available"]);
                }
            }
        } 
        // Example: Admin can manage other statuses like active, completed
        else if ($user->hasRole("Admin") && $user->can("manage_rentals")) {
            // Proceed with admin status update
            if ($newStatus === "active" && $oldStatus === "approved") {
                $rental->equipment()->update(["status" => "rented"]);
            } else if ($newStatus === "completed") {
                 // If equipment was marked rented, make it available again if no other active rental
                if ($rental->equipment->status === "rented" && !$rental->equipment->rentals()->where("status", "active")->where("id", "!=", $rental->id)->exists()) {
                    $rental->equipment()->update(["status" => "available"]);
                }
                // Potentially trigger final payment settlement, reward points calculation etc.
            }
        } 
        else {
            return response()->json(["message" => "Unauthorized to update rental to this status or in this way."], 403);
        }

        $rental->status = $newStatus;
        // Add logic for notes, timestamps for status changes, etc.
        $rental->save();

        // Trigger notifications, etc.

        return response()->json([
            "message" => "Rental status updated successfully.",
            "rental" => $rental->load(["equipment.translations", "user"])
        ]);
    }

    // Note: A full `update` method for all rental fields might be too broad.
    // Specific actions like `cancelRental`, `extendRental`, `startRental`, `completeRental` 
    // might be better as separate, permission-controlled endpoints.
    // The `updateStatus` method above is a simplified approach.

    /**
     * Remove the specified resource from storage.
     * Generally, rentals should not be hard-deleted, but marked as cancelled or archived.
     * This method is a placeholder and might not be suitable for production.
     */
    // public function destroy($id)
    // {
    //     $rental = Rental::find($id);
    //     if (!$rental) {
    //         return response()->json(["message" => "Rental not found."], 404);
    //     }
    //     // Add authorization and checks (e.g., only if not active)
    //     $rental->delete();
    //     return response()->json(["message" => "Rental deleted successfully."]);
    // }
}

