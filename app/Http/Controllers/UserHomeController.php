<?php

namespace App\Http\Controllers;

use App\Constants\DocStatus;
use App\Constants\StockEntryConstants;
use App\Models\PackingSlip;
use App\Models\StockEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserHomeController extends Controller
{
    /** @var list<string> */
    private const EXCLUDED_SALES_ORDER_STATUSES = ['Cancelled', 'Closed', 'Completed'];

    public function page()
    {
        return view('user_home');
    }

    public function pending()
    {
        $user = Auth::user();
        $owner = trim((string) ($user->wh_user ?? ''));
        $requesterName = trim((string) ($user->full_name ?? ''));
        if ($requesterName === '') {
            $requesterName = $owner;
        }
        $department = trim((string) ($user->department ?? ''));

        if ($owner === '') {
            return response()->json([
                'stock_entries' => [],
                'deliveries' => [],
            ]);
        }

        return response()->json([
            'stock_entries' => $this->stockEntries($owner, $requesterName, $department),
            'deliveries' => $this->deliveries($owner, $requesterName, $department),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function stockEntries(string $owner, string $requesterName, string $department): array
    {
        $lines = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', '=', 'sted.parent')
            ->where('ste.owner', $owner)
            ->where('ste.docstatus', DocStatus::DRAFT)
            ->where('ste.purpose', '!=', StockEntryConstants::PURPOSE_MATERIAL_RECEIPT)
            ->where(function ($query) {
                $query->where('sted.status', StockEntryConstants::STATUS_FOR_CHECKING)
                    ->orWhere(function ($issuedQuery) {
                        $issuedQuery->where('sted.status', StockEntryConstants::STATUS_ISSUED)
                            ->whereExists(function ($siblingQuery) {
                                $siblingQuery->select(DB::raw(1))
                                    ->from('tabStock Entry Detail as sted_sibling')
                                    ->whereColumn('sted_sibling.parent', 'sted.parent')
                                    ->where('sted_sibling.status', StockEntryConstants::STATUS_FOR_CHECKING);
                            });
                    });
            })
            ->orderByDesc('ste.creation')
            ->get([
                'ste.name as document_name',
                'ste.purpose',
                'ste.creation',
                'ste.remarks as parent_remarks',
                'sted.name as line_id',
                'sted.item_code',
                'sted.description',
                'sted.qty',
                'sted.uom',
                'sted.s_warehouse as warehouse',
                'sted.status',
                'sted.remarks as line_remarks',
            ]);

        return $lines
            ->groupBy('document_name')
            ->sortByDesc(fn (Collection $group) => (string) $group->first()->creation)
            ->map(function (Collection $group) use ($requesterName, $department) {
                $first = $group->first();
                $hasIssued = $group->contains(fn ($line) => $line->status === StockEntryConstants::STATUS_ISSUED);
                $status = $hasIssued ? 'pending_issuance' : 'pending_approval';
                $statusLabel = $hasIssued ? 'Pending Issuance' : 'Pending Approval';

                return $this->present(
                    $group,
                    (string) $first->document_name,
                    'Stock Entry',
                    $status,
                    $statusLabel,
                    $this->stockEntryUrl((string) $first->purpose),
                    $first->creation,
                    $requesterName,
                    $department,
                    [(string) $first->parent_remarks]
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function deliveries(string $owner, string $requesterName, string $department): array
    {
        $creationFrom = now()->subMonths((int) config('delivery.creation_months', 12))->startOfDay();

        $lines = PackingSlip::query()
            ->from('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', '=', 'psi.parent')
            ->leftJoin('tabDelivery Note Item as dri', function ($join) {
                $join->on('dri.parent', '=', 'ps.delivery_note')
                    ->on('dri.item_code', '=', 'psi.item_code');
            })
            ->leftJoin('tabDelivery Note as dr', 'dr.name', '=', 'ps.delivery_note')
            ->leftJoin('tabSales Order as so', 'so.name', '=', 'ps.sales_order')
            ->where('ps.owner', $owner)
            ->where('ps.docstatus', DocStatus::DRAFT)
            ->where('ps.creation', '>=', $creationFrom)
            ->where(function ($query) {
                $query->where('psi.status', StockEntryConstants::STATUS_FOR_CHECKING)
                    ->orWhere(function ($issuedQuery) {
                        $issuedQuery->where('psi.status', StockEntryConstants::STATUS_ISSUED)
                            ->whereExists(function ($siblingQuery) {
                                $siblingQuery->select(DB::raw(1))
                                    ->from('tabPacking Slip Item as psi_sibling')
                                    ->whereColumn('psi_sibling.parent', 'psi.parent')
                                    ->where('psi_sibling.status', StockEntryConstants::STATUS_FOR_CHECKING);
                            });
                    });
            })
            ->where(function ($query) {
                $query->whereNull('dr.name')
                    ->orWhereIn('dr.docstatus', [DocStatus::DRAFT, DocStatus::SUBMITTED]);
            })
            ->where(function ($query) {
                $query->whereNull('so.name')
                    ->orWhereNotIn('so.status', self::EXCLUDED_SALES_ORDER_STATUSES);
            })
            ->orderByDesc('ps.creation')
            ->get([
                'ps.name as document_name',
                'ps.creation',
                'ps.delivery_note',
                'psi.name as line_id',
                'psi.item_code',
                'psi.description',
                'psi.qty',
                'psi.stock_uom as uom',
                'psi.status',
                'dri.warehouse as warehouse',
            ]);

        return $lines
            ->groupBy('document_name')
            ->sortByDesc(fn (Collection $group) => (string) $group->first()->creation)
            ->map(function (Collection $group) use ($requesterName, $department) {
                $first = $group->first();
                $notes = array_filter([(string) ($first->delivery_note ?? '')]);

                return $this->present(
                    $group->unique('line_id')->values(),
                    (string) $first->document_name,
                    'Delivery Request',
                    'pending_delivery',
                    'Pending Delivery',
                    url('/picking_slip'),
                    $first->creation,
                    $requesterName,
                    $department,
                    $notes
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $extraSearch
     * @return array<string, mixed>
     */
    private function present(
        Collection $lines,
        string $number,
        string $typeLabel,
        string $status,
        string $statusLabel,
        string $viewUrl,
        mixed $creation,
        string $requesterName,
        string $department,
        array $extraSearch
    ): array {
        $created = Carbon::parse($creation);
        $daysPending = (int) abs($created->copy()->startOfDay()->diffInDays(now()->startOfDay()));
        $warehouses = $lines->pluck('warehouse')->map(fn ($warehouse) => trim((string) $warehouse))->filter()->unique()->values();
        $uoms = $lines->pluck('uom')->map(fn ($uom) => trim((string) $uom))->filter()->unique()->values();
        $qty = (float) $lines->sum(fn ($line) => (float) $line->qty);

        $searchBits = array_merge(
            [$number, $typeLabel, $requesterName, $department],
            $lines->pluck('item_code')->all(),
            $lines->pluck('description')->all(),
            $lines->pluck('line_remarks')->filter()->all(),
            $extraSearch
        );

        return [
            'number' => $number,
            'type_label' => $typeLabel,
            'created_on' => $created->format('Y-m-d'),
            'date_label' => $created->format('M j, Y'),
            'time_label' => $created->format('g:i A'),
            'days_pending' => $daysPending,
            'requester_name' => $requesterName,
            'department' => $department,
            'warehouse' => $warehouses->implode(', '),
            'warehouses' => $warehouses->all(),
            'item_count' => $lines->count(),
            'qty_label' => $this->formatQty($qty).($uoms->count() === 1 ? ' '.$uoms->first() : ''),
            'status' => $status,
            'status_label' => $statusLabel,
            'view_url' => $viewUrl,
            'search_text' => mb_strtolower(implode(' ', array_filter(array_map(fn ($bit) => trim((string) $bit), $searchBits)))),
        ];
    }

    private function stockEntryUrl(string $purpose): string
    {
        return match ($purpose) {
            StockEntryConstants::PURPOSE_MATERIAL_ISSUE => url('/material_issue'),
            StockEntryConstants::PURPOSE_MATERIAL_TRANSFER_FOR_MANUFACTURE => url('/material_transfer_for_manufacture'),
            default => url('/material_transfer'),
        };
    }

    private function formatQty(float $qty): string
    {
        $formatted = number_format($qty, 2, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
