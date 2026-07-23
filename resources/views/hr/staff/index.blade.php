@extends('layouts.hr')

@section('title', 'Nhân viên')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Nhân viên</h1>
        <a href="{{ route('hr.staff.create') }}" class="btn btn-primary">Thêm nhân viên</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Cơ sở</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staff as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->branch?->name }}</td>
                        <td>{{ $member->status }}</td>
                        <td class="text-end">
                            <a href="{{ route('hr.staff.edit', $member) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>

                            @if ($member->status === 'active')
                                <form method="POST" action="{{ route('hr.staff.lock', $member) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Khóa</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('hr.staff.unlock', $member) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">Mở khóa</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $staff->links() }}
@endsection
