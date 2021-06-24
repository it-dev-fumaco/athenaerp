@extends('item_attrib_layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center">
        <div class="col-md-8 p-2" style="margin: 0 auto !important;">
            <div class="alert alert-warning" role="alert">
                Editing of Attribute Values
                Warning:
                <ul>
                    <li>Items attributes will be updated, all link attributes and transactions will also be updated</li>
                    <li>All Items using those attribute value will also update</li>
                </ul>                
            </div>
        </div>
        <div class="col-md-8 card bg-white p-2" style="margin: 0 auto !important;">
            <div class="box">
                <div class="box-body">
                    <div class="col-md-12">
                        @if (\Session::has('success'))
                            <div class="col-md-12 alert alert-success text-center">
                                <span id="successMessage">{!! \Session::get('success') !!}</span>
                            </div>
                        @endif
                    </div>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="text-center">Attribute</th>
                                <th class="text-center">Attribute Value</th>
                                <th class="text-center">Attribute Value Update</th>
                                <th class="text-center">-</th>
                            </tr>
                            {{-- @foreach($attribute_values as $value)
                                <input type="text" id="variant_count" value="{{ $value['count'] }}"/>
                                {{ $value['count'] }}
                            @endforeach --}}
                            <form id="updateForm" action="/update_attribute" method="POST">
                                @foreach($parentDesc as $desc)
                                    <h3>Update <b>{{ $item_code }}</b> Attributes</h3>
                                    <span>Variant of <b>{{ $itemDesc->variant_of }}</b> - {{ $desc->description }}</span>
                                    <br/>
                                    <span>Item Description</span>
                                    <textarea class="form-control" rows="3" name="item_description">{{ $itemDesc->description }}</textarea>
                                    <br/>
                                @endforeach
                                <input type="text" id="itemCodeValue" name="itemCode" value="{{ $item_code }}" readonly hidden/>
                                @csrf
                                {{-- @forelse($itemAttrib as $itemAttribute) --}}
                                @forelse($attribute_values as $value)
                                    <tr>
                                        <td>
                                            <input type="text" name="attribName[]" value="{{ $value['attribute'] }}" readonly hidden/>
                                            {{ $value['attribute'] }}
                                        </td>
                                        <td>
                                            <input type="text" name="currentAttrib[]" value="{{ $value['attribute_value'] }}" readonly hidden/>
                                            {{ $value['attribute_value'] }}
                                        </td>
                                        <td class="p-1">
                                            <input type="text" id="attribVal" class="form-control" name="attrib[]" value="{{ $value['attribute_value'] }}" required/>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $value['count'] }}</span>
                                        </td>
                                    </tr>
                                    {{-- @endforeach --}}
                                    {{-- <tr>
                                        <td>
                                            <input type="text" name="attribName[]" value="{{ $itemAttribute->attribute }}" readonly hidden/>
                                            {{ $itemAttribute->attribute }}
                                        </td>
                                        <td>
                                            <input type="text" name="currentAttrib[]" value="{{ $itemAttribute->attribute_value }}" readonly hidden/>
                                            {{ $itemAttribute->attribute_value }}
                                        </td>
                                        <td class="p-1">
                                            <input type="text" id="attribVal" class="form-control" name="attrib[]" value="{{ $itemAttribute->attribute_value }}" required/>
                                            <span>has ! variants</span>
                                        </td>
                                    </tr> --}}
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center">No result(s) found.</td>
                                    <input type="text" id="emptyVal" class="form-control" name="empty" value="" required hidden/>
                                </tr>
                                @endforelse
                                <tr>
                                    <td colspan="12"><button id="submitBtn" type="submit" class="submitBtn btn btn-primary float-right">Update Attribute</button></td>
                                </tr>
                            </form>       
                        </tbody>
                    </table> 
                </div>
            </div>
        </div>
    </div>
    <div class="container" style="background-color: rgba(0,0,0,0); height: 100px;"></div>
@endsection

@section('script')

@endsection