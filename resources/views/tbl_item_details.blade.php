<div class="row p-1">
    <div class="col-md-8">
        <div class="box box-solid">
            <div class="box-header">
                <h4 class="box-title"><i class="fas fa-info-circle"></i> Item Info</h4>
            </div>
            <div class="row">
                @php
                    $img = ($item_details->item_image_path) ? '/img/' . $item_details->item_image_path : '/icon/no_img.png';
                @endphp
                <div class="col-md-4">
                    <a class='sample' data-height='720' data-lighter="{{ asset('storage/') }}{{ $img }}" data-width="1280" href="{{ asset('storage/') }}{{ $img }}">
                        <img src="{{ asset('storage/') }}{{ $img }}" style="width: 100%;">
                    </a>
                </div>
                <div class="col-md-8">
                    <dl>
                        <dt style="font-size: 15pt;" id="selected-item-code">{{ $item_details->name }}</dt>
                        <dd style="font-size: 13pt;" class="text-justify mb-2">{{ $item_details->description }}</dd>
                        <dt>Classification</dt>
                        <dd>{{ $item_details->item_classification }}</dd>
                        <dt>Brand</dt>
                        <dd>{{ $item_details->brand }}</dd>
                        <dt>Stock UoM</dt>
                        <dd>{{ $item_details->stock_uom }}</dd>
                    </dl>
                </div>
                <div class="col-md-12">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title"><i class="fa fa-box-open"></i> Stock Level</h3>
        
                        <div class="card-tools">
                            <span class="font-weight-bold m-1">Total Remaining Qty:</span>
                            <span class="badge bg-info" style="font-size: 12pt;">{{ number_format((float)$stock_level->sum('actual_qty'), 2, '.', '') }} {{ $item_details->stock_uom }}</span>
                        </div>
                      </div>
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <div class="box-body table-responsive pr-4 pl-4">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <th scope="col" >Warehouse</th>
                                        <th scope="col" class="text-center">Actual Qty</th>
                                    </thead>
                                    @forelse ($stock_level as $stock)
                                    <tr>
                                        <td>{{ $stock->warehouse }}</td>
                                        <td class="text-center">{{ number_format((float)$stock->actual_qty, 2, '.', '') }} {{ $stock->stock_uom }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2">No stocks.</td>
                                    </tr>
                                    @endforelse
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box box-solid">
            <div class="box-header">
                <h4 class="box-title"><i class="fas fa-list-alt"></i> Specification</h4>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-sm table-bordered" style="font-size: 11pt;">
                    <thead>
                        <th scope="col" >Attribute</th>
                        <th scope="col" >Attribute Value</th>
                    </thead>
                    @forelse ($item_attributes as $attr)
                    <tr>
                        <td>{{ $attr->attribute }}</td>
                        <td>{{ $attr->attribute_value }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2">No item attributes.</td>
                    </tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
</div>
