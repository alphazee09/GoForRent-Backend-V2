<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api");
        // Permissions for admins managing all payments
        $this->middleware("permission:manage_payments", ["only" => ["indexAdmin", "updatePaymentStatusAdmin"]]);
        // Users can view their own payments, owners can view payments related to their equipment rentals
    }

    /**
     * Display a listing of payments for the authenticated user (customer).
     */
    public function indexUser(Request $request)
    {
        $user = Auth::user();
        $payments = Payment::whereHas("rental", function ($query) use ($user) {
            $query->where("user_id", $user->id);
        })
        ->with(["rental.equipment.translations", "rental.user"])
        ->orderBy("created_at", "desc")
        ->paginate($request->per_page ?? 15);
        
        return response()->json($payments);
    }

    /**
     * Display a listing of payments related to rentals of equipment owned by the authenticated user.
     */
    public function indexOwner(Request $request)
    {
        $user = Auth::user();
        $payments = Payment::whereHas("rental.equipment", function ($query) use ($user) {
            $query->where("owner_id", $user->id);
        })
        ->with(["rental.equipment.translations", "rental.user"])
        ->orderBy("created_at", "desc")
        ->paginate($request->per_page ?? 15);

        return response()->json($payments);
    }

    /**
     * Display a listing of all payments (for Admin).
     */
    public function indexAdmin(Request $request)
    {
        $query = Payment::query()->with(["rental.equipment.translations", "rental.user"]);

        if ($request->has("rental_id")) {
            $query->where("rental_id", $request->rental_id);
        }
        if ($request->has("status")) {
            $query->where("status", $request->status);
        }
        // Add more filters as needed (e.g., date range, user_id)

        $payments = $query->orderBy("created_at", "desc")->paginate($request->per_page ?? 15);
        return response()->json($payments);
    }

    /**
     * Initiate a payment for a rental.
     * This is a simplified version. Real payment gateway integration is complex.
     */
    public function initiatePayment(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            "rental_id" => "required|exists:rentals,id",
            "payment_method" => "required|string|in:card,wallet,bank_transfer", // Example methods
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $rental = Rental::find($request->rental_id);

        if (!$rental || $rental->user_id !== $user->id) {
            return response()->json(["message" => "Rental not found or unauthorized."], 404);
        }

        if ($rental->payment_status === "paid" || $rental->status === "completed" || $rental->status === "cancelled") {
            return response()->json(["message" => "Payment cannot be initiated for this rental."], 400);
        }

        // Placeholder for actual payment gateway interaction
        // For now, we simulate a successful payment initiation
        $transactionId = "txn_" . uniqid();
        $amountToPay = $rental->total_price; // Or remaining amount if partial payments are allowed

        $payment = Payment::create([
            "rental_id" => $rental->id,
            "user_id" => $user->id, // Payer
            "amount" => $amountToPay,
            "payment_method" => $request->payment_method,
            "transaction_id" => $transactionId,
            "status" => "pending", // Initially pending, gateway callback would update this
        ]);
        
        // Update rental payment status
        $rental->payment_status = "processing";
        $rental->save();

        return response()->json([
            "message" => "Payment initiated successfully. Follow payment gateway instructions.",
            "payment" => $payment,
            "payment_gateway_url" => "https://example-payment-gateway.com/pay/" . $transactionId // Placeholder
        ], 201);
    }

    /**
     * Handle payment gateway callback/webhook (Simulated).
     * In a real application, this endpoint would be called by the payment gateway.
     */
    public function handleGatewayCallback(Request $request)
    {
        // This is highly dependent on the specific payment gateway
        $validator = Validator::make($request->all(), [
            "transaction_id" => "required|string",
            "status" => "required|string|in:success,failed,cancelled",
            // Other gateway-specific parameters
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $payment = Payment::where("transaction_id", $request->transaction_id)->first();

        if (!$payment) {
            return response()->json(["message" => "Payment record not found for this transaction."], 404);
        }

        if ($payment->status !== "pending") {
            return response()->json(["message" => "Payment already processed."], 400);
        }

        $newPaymentStatus = "pending";
        $newRentalPaymentStatus = $payment->rental->payment_status;
        $newRentalStatus = $payment->rental->status;

        if ($request->status === "success") {
            $newPaymentStatus = "paid";
            $newRentalPaymentStatus = "paid";
            // If rental was pending_payment, it might move to approved or active
            if ($payment->rental->status === "pending_payment") {
                $newRentalStatus = "approved"; // Or trigger next step in rental workflow
            }
        } elseif ($request->status === "failed") {
            $newPaymentStatus = "failed";
            $newRentalPaymentStatus = "failed";
             if ($payment->rental->status === "pending_payment") {
                $newRentalStatus = "payment_failed"; 
            }
        } elseif ($request->status === "cancelled") {
            $newPaymentStatus = "cancelled_by_gateway";
            $newRentalPaymentStatus = "pending"; // Or failed, depending on flow
             if ($payment->rental->status === "pending_payment") {
                $newRentalStatus = "payment_failed"; // Or back to pending_approval if payment was initial step
            }
        }

        $payment->status = $newPaymentStatus;
        $payment->gateway_response = json_encode($request->all()); // Store full gateway response
        $payment->save();

        $rental = $payment->rental;
        $rental->payment_status = $newRentalPaymentStatus;
        $rental->status = $newRentalStatus; // Update rental status based on payment
        $rental->save();

        // Trigger notifications, update equipment status if rental is now active, etc.
        if ($newPaymentStatus === "paid" && $newRentalStatus === "approved") {
            // Potentially create contract, send notifications, etc.
        }

        return response()->json(["message" => "Payment callback processed."]);
    }

    /**
     * Display the specified payment resource.
     */
    public function show($id)
    {
        $user = Auth::user();
        $payment = Payment::with(["rental.equipment.translations", "rental.user"])->find($id);

        if (!$payment) {
            return response()->json(["message" => "Payment not found."], 404);
        }

        // Authorization: User can see their own, Owner can see payments for their equipment rentals, Admin can see all
        $rental = $payment->rental;
        $isOwner = $rental->equipment->owner_id === $user->id;

        if ($rental->user_id !== $user->id && !$isOwner && !$user->hasRole("Admin")) {
             if (!($isOwner && $user->can("view_payments")) && !($rental->user_id === $user->id && $user->can("view_payments"))) {
                return response()->json(["message" => "Unauthorized to view this payment."], 403);
             }
        }

        return response()->json($payment);
    }
    
    /**
     * Update payment status (Admin action).
     */
    public function updatePaymentStatusAdmin(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(["message" => "Payment not found."], 404);
        }

        $validator = Validator::make($request->all(), [
            "status" => "required|in:pending,paid,failed,refunded,cancelled_by_gateway",
            "admin_notes" => "nullable|string"
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $oldStatus = $payment->status;
        $newStatus = $request->status;

        $payment->status = $newStatus;
        if ($request->has("admin_notes")) {
            // Assuming an admin_notes field on the payments table
            // $payment->admin_notes = $request->admin_notes;
        }
        $payment->save();

        // If payment status changes, rental status might also need an update
        $rental = $payment->rental;
        if ($newStatus === "paid" && $oldStatus !== "paid") {
            $rental->payment_status = "paid";
            if ($rental->status === "pending_payment" || $rental->status === "payment_failed") {
                $rental->status = "approved"; // Or trigger next step in rental workflow
            }
        } elseif ($newStatus === "failed" && $oldStatus !== "failed") {
            $rental->payment_status = "failed";
            if ($rental->status === "pending_payment") {
                 $rental->status = "payment_failed";
            }
        } // Add more logic for other status changes like refunded
        $rental->save();

        return response()->json([
            "message" => "Payment status updated successfully by admin.",
            "payment" => $payment->load(["rental.equipment.translations", "rental.user"])
        ]);
    }
}

