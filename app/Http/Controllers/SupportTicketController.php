<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $tickets = SupportTicket::with(['customer', 'user'])->latest()->paginate(15);
        } else {
            $customer = $user->customer;
            $tickets = $customer->supportTickets()->with('user')->latest()->paginate(15);
        }

        return ApiResponse::success($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        $validator = Validator::make($request->all(), [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $ticket = SupportTicket::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'subject' => $request->subject,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'open',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin_reply' => false,
        ]);

        return ApiResponse::success($ticket->load('messages'), 'Ticket submitted successfully', [], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = SupportTicket::with(['customer', 'user', 'messages.user']);

        if (!$user->isSuperAdmin()) {
            $query->where('customer_id', $user->customer_id);
        }

        $ticket = $query->where(fn ($q) => $q->where('uuid', $id)->orWhere('ticket_number', $id))->firstOrFail();
        return ApiResponse::success($ticket);
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $query = SupportTicket::query();
        if (!$user->isSuperAdmin()) {
            $query->where('customer_id', $user->customer_id);
        }

        $ticket = $query->where(fn ($q) => $q->where('uuid', $id)->orWhere('ticket_number', $id))->firstOrFail();

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string'],
            'status' => ['nullable', 'in:open,pending,answered,resolved,closed'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin_reply' => $user->isSuperAdmin(),
        ]);

        if ($user->isSuperAdmin()) {
            $ticket->update(['status' => $request->input('status', 'answered')]);
        } else {
            $ticket->update(['status' => 'open']);
        }

        return ApiResponse::success($message, 'Reply added successfully');
    }
}
