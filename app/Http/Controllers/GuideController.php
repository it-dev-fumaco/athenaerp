<?php

namespace App\Http\Controllers;

use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuideController extends Controller
{
    use GeneralTrait;

    private function getGuideImages(string $path, string $title): array
    {
        $files = Storage::files($path);

        $files = collect($files)->filter(function ($file) use ($path, $title) {
            return Str::startsWith($file, "$path/$title");
        })->all();

        $images = collect($files)->mapWithKeys(function ($image) {
            [$directory, $filename] = explode('/', $image);
            [$id] = explode('.', $filename);

            return [$id => $this->base64Image($image)];
        })->all();

        $noImg = $this->base64Image('icon/no_img.png');
        $images['no_img'] = $noImg;

        return $images;
    }

    public function beginningInventory()
    {
        $images = $this->getGuideImages('user_manual_img', 'beginning_inventory');

        return view('consignment.user_manual.beginning_inventory', compact('images'));
    }

    public function salesReportEntry()
    {
        $images = $this->getGuideImages('user_manual_img', 'sales_report');

        return view('consignment.user_manual.sales_report', compact('images'));
    }

    public function stockTransfer()
    {
        $images = $this->getGuideImages('user_manual_img', 'stock_transfer');

        return view('consignment.user_manual.stock_transfer', compact('images'));
    }

    public function damagedItems()
    {
        $images = $this->getGuideImages('user_manual_img', 'damaged_items');

        return view('consignment.user_manual.damaged_items', compact('images'));
    }

    public function stockReceiving()
    {
        $images = $this->getGuideImages('user_manual_img', 'stock_receiving');

        return view('consignment.user_manual.stock_receiving', compact('images'));
    }

    public function inventoryAudit()
    {
        $images = $this->getGuideImages('user_manual_img', 'inventory_audit');

        return view('consignment.user_manual.inventory_audit', compact('images'));
    }

    public function consignmentDashboard()
    {
        $images = $this->getGuideImages('user_manual_img', 'cs');

        return view('consignment.user_manual.consignment_dashboard', compact('images'));
    }

    public function beginningEntries()
    {
        $images = $this->getGuideImages('user_manual_img', 'cs');

        return view('consignment.user_manual.beginning_entries', compact('images'));
    }

    public function inventoryReport()
    {
        $images = $this->getGuideImages('user_manual_img', 'cs');

        return view('consignment.user_manual.inventory_report', compact('images'));
    }

    public function inventorySummary()
    {
        $images = $this->getGuideImages('user_manual_img', 'cs');

        return view('consignment.user_manual.inventory_summary', compact('images'));
    }

    public function stockToReceive()
    {
        $images = $this->getGuideImages('user_manual_img', 'cs');

        return view('consignment.user_manual.stock_to_receive', compact('images'));
    }

    public function consignmentStockTransfer()
    {
        $images = $this->getGuideImages('user_manual_img', 'cs');
        $images['receiving-of-stocks'] = $this->base64Image('user_manual_img/receiving-of-stocks.png');

        return view('consignment.user_manual.consignment_stock_transfer', compact('images'));
    }
}
