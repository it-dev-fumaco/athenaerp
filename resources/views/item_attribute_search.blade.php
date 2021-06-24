@extends('item_attrib_layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center">
        <div class="col-md-10 text-center bg-white" style="margin: 0 auto !important;">
            <form action="/search" class="form-inline mb-2" method="GET">
                <div class="form-group" style="margin: 0 auto !important;">   
                    <label>Item Code </label>
                    <input type="text" class="form-control m-2" id="itemCode" name="item_code" value="{{ request('item_code') }}" required/>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
            <div class="col-md-10 text-center bg-white" style="margin: 0 auto !important;">
                @if( request('item_code') == "")
                    <span>Enter item code to start searching</span>
                @elseif(count($itemAttrib) > 0)
                    <div class="row">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>Attribute</th>
                                    <th>Attribute Value</th>
                                </tr>
                                @forelse($itemAttrib as $itemAttribute)
                                    <tr>
                                        <td>{{ $itemAttribute->attribute }}</td>
                                        <td>{{ $itemAttribute->attribute_value }}</td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="row">
                        <div class="col-md-4" style="margin: 0 auto !important; z-index: 999 !important">
                            <a href="/update_form?u_item_code={{ request('item_code') }}">
                                <button type="submit" class="col-md-12 btn btn-primary btn-lg">Update Attribute</button>
                            </a>
                        </div>
                        <div class="col-md-4" style="margin: 0 auto !important; z-index: 999 !important">
                            <a href="/add_form?c_item_code={{ request('item_code') }}">
                                <button type="submit" class="col-md-12 btn btn-primary btn-lg">Add Attribute</button>
                            </a>
                        </div>
                    </div>
                    <div class="row" style="background-color: rgba(0,0,0,0); height: 100px;"></div>
                @elseif(count($itemAttrib) == 0)
                    <div class="col-md-12 alert alert-warning text-center" style="margin: 0 auto !important;">
                        <span>Item is not a Stock Item!</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
        
@endsection

@section('script')

@endsection