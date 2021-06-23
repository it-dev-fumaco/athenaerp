@extends('item_attrib_layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center">
        <div class="col-md-10 text-center" style="margin: 0 auto !important;">
            <form action="/search" class="form-inline mb-2" method="GET">
                <div class="form-group" style="margin: 0 auto !important;">   
                    <label>Item Code </label>
                    <input type="text" class="form-control m-2" id="itemCode" name="item_code" value="{{ request('item_code') }}"/>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
            <div class="col-md-10 text-center" style="margin: 0 auto !important;">
                @if (\Session::has('duplicateValue'))
                    &nbsp;<br/>
                    <div class="col-md-11 alert alert-warning text-center" style="margin: 0 auto;">
                        <span id="duplicateValue">{!! \Session::get('duplicateValue') !!}</span>
                    </div>
                    <br/>
                @elseif (\Session::has('insertSuccess'))
                    <div class="col-md-12 alert alert-success text-center">
                        <span id="insertSuccess">{!! \Session::get('insertSuccess') !!}</span>
                    </div>
                    <br/>
                @elseif (\Session::has('success'))
                    <div class="col-md-12 alert alert-success text-center">
                        <span id="successMessage">{!! \Session::get('success') !!}</span>
                    </div>
                    <br/>
                @elseif(count($itemAttrib) == 0)
                    <div class="col-md-12 alert alert-warning text-center" style="margin: 0 auto !important;">
                        <span>Item is not a Stock Item!</span>
                    </div>
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
                        <div class="col-md-4" style="margin: 0 auto !important;">
                            {{-- <form action="/update_form" class="form-inline mb-2" method="POST">
                                @csrf
                                <input type="text" name="U_item_code" value="{{ request('item_code') }}" readonly hidden/>
                                <button type="submit" class="col-md-12 btn btn-primary btn-lg">Update Attribute</button>
                            </form> --}}
                            <a href="/update_form?item_code={{ request('item_code') }}">Try</a>
                        </div>
                        <div class="col-md-4" style="margin: 0 auto !important;">
                            <form action="/add_form" class="form-inline mb-2" method="POST">
                                @csrf
                                <input type="text" name="C_item_code" value="{{ request('item_code') }}" readonly hidden/>
                                <button type="submit" class="col-md-12 btn btn-primary btn-lg">Add Attribute</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
        
@endsection

@section('script')

@endsection