<!-- First you need to extend the CB layout -->
@extends('crudbooster::admin_template')
<!-- Your html goes here -->
@section('content')
<div class='panel panel-default'>
    <form method='post' action='{{CRUDBooster::mainpath('add-save')}}' enctype="multipart/form-data">
        <div class='panel-body'>
            <h1>
                Presione <strong><span style="color:#008D4C;">↑</span> Add Data</strong> para subir los precios
            </h1>
        </div>
    </form>
</div>
@endsection