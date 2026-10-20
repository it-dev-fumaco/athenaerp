<?php

namespace App\Http\Controllers;

use App\Constants\DocStatus;
use App\Constants\StockEntryConstants;
use App\Http\Middleware\EnsureInformationTechnologyAccess;
use App\Models\PackingSlip;
use App\Models\StockEntry;
use App\Models\User;
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
        $user = Auth::user();
        if ($user && ($user->user_group ?? '') === 'Warehouse Personnel') {
            return redirect($user->homePath());
        }

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
        $seesAll = $this->seesAllPending($user);

        if ($owner === '' && ! $seesAll) {
            return response()->json([
                'first_name' => $this->firstName($requesterName),
                'sees_all' => false,
                'erp_web_base_url' => $this->erpWebBaseUrl(),
                'stock_entries' => [],
                'deliveries' => [],
            ]);
        }

        return response()->json([
            'first_name' => $this->firstName($requesterName),
            'sees_all' => $seesAll,
            'erp_web_base_url' => $this->erpWebBaseUrl(),
            'stock_entries' => $this->stockEntries($owner, $requesterName, $department, $seesAll),
            'deliveries' => $this->deliveries($owner, $requesterName, $department, $seesAll),
        ]);
    }

    private function erpWebBaseUrl(): string
    {
        return rtrim((string) config('erp.web_base_url'), '/');
    }

    private function seesAllPending(object $user): bool
    {
        return ($user->user_group ?? '') === 'Director'
            || ($user->department ?? '') === EnsureInformationTechnologyAccess::DEPARTMENT;
    }

    /**
     * @return array<string, array{name: string, department: string}>
     */
    private function requestersByOwner(Collection $lines): array
    {
        $owners = $lines->pluck('owner')->map(fn ($owner) => trim((string) $owner))->filter()->unique()->values();
        if ($owners->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('wh_user', $owners->all())
            ->get(['wh_user', 'full_name', 'department'])
            ->mapWithKeys(function ($user) {
                $owner = (string) $user->wh_user;
                $name = trim((string) ($user->full_name ?? ''));

                return [$owner => [
                    'name' => $name !== '' ? $name : $owner,
                    'department' => trim((string) ($user->department ?? '')),
                ]];
            })
            ->all();
    }

    /**
     * @param  array<string, array{name: string, department: string}>  $profiles
     * @return array{0: string, 1: string}
     */
    private function requesterFor(object $line, string $fallbackName, string $fallbackDepartment, bool $seesAll, array $profiles): array
    {
        if (! $seesAll) {
            return [$fallbackName, $fallbackDepartment];
        }

        $owner = trim((string) ($line->owner ?? ''));
        $profile = $profiles[$owner] ?? null;

        return [
            $profile['name'] ?? ($owner !== '' ? $owner : $fallbackName),
            $profile['department'] ?? '',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function stockEntries(string $owner, string $requesterName, string $department, bool $seesAll = false): array
    {
        $lines = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', '=', 'sted.parent')
            ->when(! $seesAll, fn ($query) => $query->where('ste.owner', $owner))
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
            ->orderBy('ste.creation')
            ->get([
                'ste.name as document_name',
                'ste.owner',
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

        $profiles = $seesAll ? $this->requestersByOwner($lines) : [];

        return $lines
            ->groupBy('document_name')
            ->sortByDesc(fn (Collection $group) => (string) $group->first()->creation)
            ->map(function (Collection $group) use ($requesterName, $department, $seesAll, $profiles) {
                $first = $group->first();
                [$name, $dept] = $this->requesterFor($first, $requesterName, $department, $seesAll, $profiles);

                $openLine = $group->first(fn ($line) => (string) ($line->status ?? '') === StockEntryConstants::STATUS_FOR_CHECKING) ?? $first;
                $row = $this->present(
                    $group,
                    (string) $first->document_name,
                    'Stock Entry',
                    'pending_issuance',
                    'Pending Issuance',
                    $this->stockEntryUrl((string) $first->purpose),
                    $first->creation,
                    $name,
                    $dept,
                    [(string) $first->parent_remarks]
                );
                $row['line_id'] = (string) $openLine->line_id;
                $row['modal'] = 'ste';
                $row['checkout_url'] = $this->stockEntryCheckoutUrl((string) $first->purpose);

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function deliveries(string $owner, string $requesterName, string $department, bool $seesAll = false): array
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
            ->when(! $seesAll, fn ($query) => $query->where('ps.owner', $owner))
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
            ->orderBy('ps.creation')
            ->get([
                'ps.name as document_name',
                'ps.owner',
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

        $profiles = $seesAll ? $this->requestersByOwner($lines) : [];

        return $lines
            ->groupBy('document_name')
            ->sortBy(fn (Collection $group) => (string) $group->first()->creation)
            ->map(function (Collection $group) use ($requesterName, $department, $seesAll, $profiles) {
                $first = $group->first();
                $notes = array_filter([(string) ($first->delivery_note ?? '')]);
                [$name, $dept] = $this->requesterFor($first, $requesterName, $department, $seesAll, $profiles);

                $documentLines = $group->unique('line_id')->values();
                $openLine = $documentLines->first(fn ($line) => (string) ($line->status ?? '') === StockEntryConstants::STATUS_FOR_CHECKING) ?? $documentLines->first();
                $row = $this->present(
                    $documentLines,
                    (string) $first->document_name,
                    'Delivery Request',
                    'pending_delivery',
                    'Pending Delivery',
                    url('/picking_slip'),
                    $first->creation,
                    $name,
                    $dept,
                    $notes
                );
                $row['line_id'] = (string) $openLine->line_id;
                $row['modal'] = 'ps';
                $row['checkout_url'] = url('/checkout_picking_slip');

                return $row;
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
            'primary_item' => $this->primaryItem($lines),
            'qty_label' => $this->formatQty($qty).($uoms->count() === 1 ? ' '.$uoms->first() : ''),
            'status' => $status,
            'age_label' => $created->diffForHumans(),
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

    private function stockEntryCheckoutUrl(string $purpose): string
    {
        return $purpose === StockEntryConstants::PURPOSE_MATERIAL_TRANSFER
            ? url('/submit_internal_transfer')
            : url('/submit_transaction');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentActivity(string $owner): array
    {
        return collect($this->recentStockActivities($owner))
            ->merge($this->recentDeliveryActivities($owner))
            ->sortByDesc('sort_at')
            ->take(8)
            ->map(function (array $row) {
                unset($row['sort_at']);

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentStockActivities(string $owner): array
    {
        $parents = StockEntry::query()
            ->where('owner', $owner)
            ->whereIn('docstatus', [DocStatus::DRAFT, DocStatus::SUBMITTED])
            ->where('purpose', '!=', StockEntryConstants::PURPOSE_MATERIAL_RECEIPT)
            ->orderByDesc('modified')
            ->orderByDesc('creation')
            ->limit(8)
            ->get(['name', 'purpose', 'docstatus', 'creation', 'modified']);

        if ($parents->isEmpty()) {
            return [];
        }

        $lines = DB::table('tabStock Entry Detail')
            ->whereIn('parent', $parents->pluck('name'))
            ->orderBy('idx')
            ->get(['parent', 'item_code', 'description', 'qty', 'uom', 's_warehouse', 't_warehouse'])
            ->groupBy('parent');

        return $parents->map(function ($parent) use ($lines) {
            $group = collect($lines->get($parent->name, []));
            $at = Carbon::parse($parent->modified ?: $parent->creation);
            $purpose = (string) $parent->purpose;
            $isTransfer = in_array($purpose, [
                StockEntryConstants::PURPOSE_MATERIAL_TRANSFER,
                StockEntryConstants::PURPOSE_MATERIAL_TRANSFER_FOR_MANUFACTURE,
            ], true);

            return [
                'kind' => $isTransfer ? 'transfer' : 'stock_entry',
                'title' => 'Stock Entry '.$parent->name.' '.$this->activityVerb((int) $parent->docstatus),
                'detail' => $this->stockActivityDetail($group, $isTransfer),
                'time_label' => $at->diffForHumans(),
                'view_url' => $this->stockEntryUrl($purpose),
                'sort_at' => $at->getTimestamp(),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentDeliveryActivities(string $owner): array
    {
        $parents = PackingSlip::query()
            ->where('owner', $owner)
            ->whereIn('docstatus', [DocStatus::DRAFT, DocStatus::SUBMITTED])
            ->orderByDesc('modified')
            ->orderByDesc('creation')
            ->limit(8)
            ->get(['name', 'docstatus', 'creation', 'modified', 'delivery_note', 'sales_order']);

        if ($parents->isEmpty()) {
            return [];
        }

        $lines = DB::table('tabPacking Slip Item as psi')
            ->join('tabPacking Slip as ps', 'ps.name', '=', 'psi.parent')
            ->leftJoin('tabDelivery Note Item as dri', function ($join) {
                $join->on('dri.parent', '=', 'ps.delivery_note')
                    ->on('dri.item_code', '=', 'psi.item_code');
            })
            ->whereIn('psi.parent', $parents->pluck('name'))
            ->orderBy('psi.idx')
            ->get([
                'psi.parent',
                'psi.name as line_id',
                'psi.item_code',
                'psi.description',
                'psi.qty',
                'psi.stock_uom as uom',
                'dri.warehouse as warehouse',
            ])
            ->unique('line_id')
            ->groupBy('parent');

        return $parents->map(function ($parent) use ($lines) {
            $group = collect($lines->get($parent->name, []));
            $at = Carbon::parse($parent->modified ?: $parent->creation);

            return [
                'kind' => 'delivery',
                'title' => 'Delivery '.$parent->name.' '.$this->activityVerb((int) $parent->docstatus),
                'detail' => $this->deliveryActivityDetail($parent, $group),
                'time_label' => $at->diffForHumans(),
                'view_url' => url('/picking_slip'),
                'sort_at' => $at->getTimestamp(),
            ];
        })->all();
    }

    private function stockActivityDetail(Collection $lines, bool $isTransfer): string
    {
        $first = $lines->first();
        if ($first === null) {
            return '';
        }

        $phrase = $this->qtyItemPhrase($first);
        $from = trim((string) ($first->s_warehouse ?? ''));
        $to = trim((string) ($first->t_warehouse ?? ''));
        if ($isTransfer && $from !== '' && $to !== '') {
            $text = 'Transferred '.$phrase.' from '.$from.' to '.$to;
        } else {
            $warehouse = $from !== '' ? $from : $to;
            $text = $phrase.($warehouse !== '' ? ' · '.$warehouse : '');
        }

        return $this->withMoreItems($text, $lines->count());
    }

    private function deliveryActivityDetail(object $parent, Collection $lines): string
    {
        $reference = trim((string) ($parent->sales_order ?: $parent->delivery_note));
        $first = $lines->first();
        if ($first === null) {
            return $reference !== '' ? 'For '.$reference : '';
        }

        $parts = [];
        if ($reference !== '') {
            $parts[] = 'For '.$reference;
        }
        $parts[] = $this->qtyItemPhrase($first);
        $warehouse = trim((string) ($first->warehouse ?? ''));
        if ($warehouse !== '') {
            $parts[] = $warehouse;
        }

        return $this->withMoreItems(implode(' · ', $parts), $lines->count());
    }

    private function qtyItemPhrase(object $line): string
    {
        $qty = $this->formatQty((float) ($line->qty ?? 0));
        $uom = trim((string) ($line->uom ?? ''));
        $code = trim((string) ($line->item_code ?? ''));
        $description = $this->shortText((string) ($line->description ?? ''), 60);
        $item = $code;
        if ($description !== '' && strcasecmp($description, $code) !== 0) {
            $item = $code !== '' ? $code.' ('.$description.')' : $description;
        }

        $qtyPart = trim($qty.($uom !== '' ? ' '.$uom : ''));
        if ($item === '') {
            return $qtyPart;
        }

        return trim($qtyPart.' of '.$item);
    }

    private function withMoreItems(string $text, int $count): string
    {
        $extra = max(0, $count - 1);
        if ($extra === 0) {
            return $text;
        }

        return $text.' and '.$extra.' more '.($extra === 1 ? 'item' : 'items');
    }

    private function primaryItem(Collection $lines): string
    {
        $first = $lines->first();
        if ($first === null) {
            return '';
        }

        $description = $this->shortText((string) ($first->description ?? ''), 80);
        if ($description !== '') {
            return $description;
        }

        return trim((string) ($first->item_code ?? ''));
    }

    private function shortText(string $value, int $limit = 80): string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($value)));
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1)).'…';
    }

    private function activityVerb(int $docstatus): string
    {
        return $docstatus === DocStatus::SUBMITTED ? 'submitted' : 'created';
    }

    private function firstName(string $fullName): string
    {
        $name = trim($fullName);
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        return (string) ($parts[0] ?? '');
    }

    private function formatQty(float $qty): string
    {
        $formatted = number_format($qty, 2, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
