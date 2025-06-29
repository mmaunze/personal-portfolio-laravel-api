<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Display a listing of contacts
     */
    public function index(Request $request)
    {
        try {
            $query = Contact::query();

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter recent contacts
            if ($request->filled('recent_days')) {
                $query->recent($request->recent_days);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $contacts = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $contacts,
                'meta' => [
                    'total_contacts' => Contact::count(),
                    'new_contacts' => Contact::new()->count(),
                    'read_contacts' => Contact::read()->count(),
                    'replied_contacts' => Contact::replied()->count(),
                    'archived_contacts' => Contact::archived()->count(),
                    'spam_contacts' => Contact::spam()->count(),
                    'recent_contacts' => Contact::recent(7)->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar contactos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created contact (public endpoint)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Rate limiting check
            $ipAddress = $request->ip();
            $email = $request->email;

            // Check for spam (too many contacts from same IP or email)
            if (Contact::countFromIp($ipAddress, 1) >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Muitos contactos enviados. Tente novamente mais tarde.'
                ], 429);
            }

            if (Contact::countFromEmail($email, 24) >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limite de contactos por email atingido. Tente novamente amanhã.'
                ], 429);
            }

            $contactData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company' => $request->company,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => 'new',
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'referrer' => $request->header('referer'),
                    'accept_language' => $request->header('accept-language')
                ]
            ];

            $contact = Contact::create($contactData);

            return response()->json([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso. Responderemos em breve.',
                'data' => [
                    'id' => $contact->id,
                    'created_at' => $contact->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar mensagem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified contact
     */
    public function show(Contact $contact)
    {
        try {
            // Mark as read if it's new
            if ($contact->status === 'new') {
                $contact->markAsRead();
            }

            return response()->json([
                'success' => true,
                'data' => $contact->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter contacto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified contact
     */
    public function update(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,read,replied,archived,spam',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = ['status' => $request->status];

            // Update timestamps based on status
            switch ($request->status) {
                case 'read':
                    $updateData['read_at'] = now();
                    break;
                case 'replied':
                    $updateData['replied_at'] = now();
                    if (!$contact->read_at) {
                        $updateData['read_at'] = now();
                    }
                    break;
                case 'new':
                    $updateData['read_at'] = null;
                    $updateData['replied_at'] = null;
                    break;
            }

            // Add notes to metadata
            if ($request->filled('notes')) {
                $metadata = $contact->metadata ?: [];
                $metadata['notes'] = $request->notes;
                $metadata['updated_by'] = auth()->user()->name;
                $metadata['updated_at'] = now()->toISOString();
                $updateData['metadata'] = $metadata;
            }

            $contact->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Contacto actualizado com sucesso',
                'data' => $contact->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao actualizar contacto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified contact
     */
    public function destroy(Contact $contact)
    {
        try {
            $contact->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contacto eliminado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao eliminar contacto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark contact as read
     */
    public function markAsRead(Contact $contact)
    {
        try {
            $contact->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Contacto marcado como lido',
                'data' => $contact->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar contacto como lido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark contact as replied
     */
    public function markAsReplied(Contact $contact)
    {
        try {
            $contact->markAsReplied();

            return response()->json([
                'success' => true,
                'message' => 'Contacto marcado como respondido',
                'data' => $contact->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar contacto como respondido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark contact as spam
     */
    public function markAsSpam(Contact $contact)
    {
        try {
            $contact->markAsSpam();

            return response()->json([
                'success' => true,
                'message' => 'Contacto marcado como spam',
                'data' => $contact->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar contacto como spam',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive contact
     */
    public function archive(Contact $contact)
    {
        try {
            $contact->markAsArchived();

            return response()->json([
                'success' => true,
                'message' => 'Contacto arquivado com sucesso',
                'data' => $contact->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao arquivar contacto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contact statistics
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_contacts' => Contact::count(),
                'new_contacts' => Contact::new()->count(),
                'read_contacts' => Contact::read()->count(),
                'replied_contacts' => Contact::replied()->count(),
                'archived_contacts' => Contact::archived()->count(),
                'spam_contacts' => Contact::spam()->count(),
                'unread_contacts' => Contact::unread()->count(),
                'recent_contacts' => [
                    'today' => Contact::whereDate('created_at', today())->count(),
                    'this_week' => Contact::recent(7)->count(),
                    'this_month' => Contact::recent(30)->count()
                ],
                'response_rate' => Contact::count() > 0 ? 
                    round((Contact::replied()->count() / Contact::count()) * 100, 1) : 0,
                'avg_response_time' => $this->getAverageResponseTime(),
                'top_subjects' => Contact::selectRaw('subject, COUNT(*) as count')
                    ->groupBy('subject')
                    ->orderByDesc('count')
                    ->limit(5)
                    ->pluck('count', 'subject')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter estatísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate average response time
     */
    private function getAverageResponseTime()
    {
        $repliedContacts = Contact::replied()
            ->whereNotNull('replied_at')
            ->get(['created_at', 'replied_at']);

        if ($repliedContacts->isEmpty()) {
            return 0;
        }

        $totalHours = $repliedContacts->sum(function ($contact) {
            return $contact->created_at->diffInHours($contact->replied_at);
        });

        return round($totalHours / $repliedContacts->count(), 1);
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:mark_read,mark_replied,archive,mark_spam,delete',
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'integer|exists:contacts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contacts = Contact::whereIn('id', $request->contact_ids);
            $count = $contacts->count();

            switch ($request->action) {
                case 'mark_read':
                    $contacts->update([
                        'status' => 'read',
                        'read_at' => now()
                    ]);
                    $message = "{$count} contacto(s) marcado(s) como lido(s)";
                    break;
                    
                case 'mark_replied':
                    $contacts->update([
                        'status' => 'replied',
                        'replied_at' => now(),
                        'read_at' => now()
                    ]);
                    $message = "{$count} contacto(s) marcado(s) como respondido(s)";
                    break;
                    
                case 'archive':
                    $contacts->update(['status' => 'archived']);
                    $message = "{$count} contacto(s) arquivado(s)";
                    break;
                    
                case 'mark_spam':
                    $contacts->update(['status' => 'spam']);
                    $message = "{$count} contacto(s) marcado(s) como spam";
                    break;
                    
                case 'delete':
                    $contacts->delete();
                    $message = "{$count} contacto(s) eliminado(s)";
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar operação em lote',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export contacts
     */
    public function export(Request $request)
    {
        try {
            $query = Contact::query();

            // Apply filters
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $contacts = $query->orderBy('created_at', 'desc')->get();

            // Convert to CSV format
            $csvData = [];
            $csvData[] = [
                'ID', 'Nome', 'Email', 'Telefone', 'Empresa', 'Assunto', 
                'Mensagem', 'Status', 'Data de Criação', 'Data de Leitura', 'Data de Resposta'
            ];

            foreach ($contacts as $contact) {
                $csvData[] = [
                    $contact->id,
                    $contact->name,
                    $contact->email,
                    $contact->phone,
                    $contact->company,
                    $contact->subject,
                    $contact->short_message,
                    $contact->status_label,
                    $contact->created_at->format('d/m/Y H:i'),
                    $contact->read_at ? $contact->read_at->format('d/m/Y H:i') : '',
                    $contact->replied_at ? $contact->replied_at->format('d/m/Y H:i') : ''
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $csvData,
                'meta' => [
                    'total_exported' => count($csvData) - 1, // Subtract header row
                    'export_date' => now()->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao exportar contactos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

