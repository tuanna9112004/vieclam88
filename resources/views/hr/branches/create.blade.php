@extends('layouts.hr')

@section('title', 'Thêm cơ sở')

@section('content')
    <h1 class="h4 mb-4">Thêm cơ sở</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('hr.branches.store') }}" novalidate style="max-width: 560px;">
        @csrf

        <div class="mb-3">
            <label for="code" class="form-label">Mã cơ sở</label>
            <input type="text" class="form-control" id="code" name="code" value="{{ old('code') }}" required>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">Tên</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="phone" class="form-label">Điện thoại</label>
                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
            </div>
            <div class="col-md-6">
                <label for="zalo" class="form-label">Zalo</label>
                <input type="text" class="form-control" id="zalo" name="zalo" value="{{ old('zalo') }}">
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
        </div>

        <div class="mb-3">
            <label for="administrative_unit_id" class="form-label">Đơn vị hành chính</label>
            <select class="form-select" id="administrative_unit_id" name="administrative_unit_id" required>
                <option value="">-- Chọn --</option>
                @foreach ($administrativeUnits as $unit)
                    <option value="{{ $unit->id }}" @selected(old('administrative_unit_id') == $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="address_detail" class="form-label">Địa chỉ</label>
            <input type="text" class="form-control" id="address_detail" name="address_detail" value="{{ old('address_detail') }}">
        </div>

        <button type="submit" class="btn btn-primary w-100">Tạo cơ sở</button>
    </form>
@endsection
