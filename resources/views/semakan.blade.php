@extends('layout')
@section('content')

{{-- Carian --}}
<form>
    <div class="form-group">
        <label for="tarikhccc"><b>Tarikh CCC</b></label>
        <input type="date" class="form-control" name="tarikhccc">
    </div>
    <button type="reset" class="btn btn-secondary">Semula</button>
    <button type="submit" class="btn btn-success">Cari</button>
</form>

{{-- Output --}}
@if (request()->has('tarikhccc'))
<table class="table table-hover table-card mt-5 text-center">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Nama</th>
            <th scope="col">No KP</th>
            <th scope="col">Tindakan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($pembeli_rs as $pembeli)
        <tr>
            <th scope="row">{{$loop->index + 1}}</th>
            <td>{{$pembeli->nama}}</td>
            <td>{{$pembeli->no_kp}}</td>
            <td>
                <a href="/permohonan/{{$pembeli->id}}" class="btn btn-primary">Mohon</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5">Tiada Rekod ..</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endif


@endsection