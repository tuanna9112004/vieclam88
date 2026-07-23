@extends('layouts.hr')

@section('title', 'Cơ sở')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Cơ sở</h1>
        @can('create', \App\Models\Branch::class)
            <a href="{{ route('hr.branches.create') }}" class="btn btn-primary">Thêm cơ sở</a>
        @endcan
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Đơn vị hành chính</th>
                    <th>Điện thoại/Zalo</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($branches as $branch)
                    <tr>
                        <td>{{ $branch->code }}</td>
                        <td>{{ $branch->name }}</td>
                        <td>{{ $branch->ward ? $branch->ward->name.', '.$branch->ward->province?->name : $branch->administrativeUnit?->name }}</td>
                        <td>{{ $branch->phone }}{{ $branch->phone && $branch->zalo ? ' / ' : '' }}{{ $branch->zalo }}</td>
                        <td>{{ $branch->status === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' }}</td>
                        <td class="text-end">
                            <a href="{{ route('hr.branches.edit', $branch) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>

                            @can('delete', $branch)
                                <form method="POST" action="{{ route('hr.branches.destroy', $branch) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $branches->links() }}

    @if ($trashedBranches->isNotEmpty())
        <h2 class="h6 mt-5 mb-3">Cơ sở đã xóa</h2>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trashedBranches as $branch)
                        <tr>
                            <td>{{ $branch->code }}</td>
                            <td>{{ $branch->name }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('hr.branches.restore', $branch) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">Khôi phục</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
