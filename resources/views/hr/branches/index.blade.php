<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Cơ sở — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h4 mb-0">Cơ sở</h1>
                <a href="{{ route('hr.branches.create') }}" class="btn btn-primary">Thêm cơ sở</a>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

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
                            <td>{{ $branch->administrativeUnit?->name }}</td>
                            <td>{{ $branch->phone }}{{ $branch->phone && $branch->zalo ? ' / ' : '' }}{{ $branch->zalo }}</td>
                            <td>{{ $branch->status === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' }}</td>
                            <td class="text-end">
                                <a href="{{ route('hr.branches.edit', $branch) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>

                                <form method="POST" action="{{ route('hr.branches.destroy', $branch) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $branches->links() }}

            @if ($trashedBranches->isNotEmpty())
                <h2 class="h6 mt-5 mb-3">Cơ sở đã xóa</h2>

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
            @endif
        </div>
    </body>
</html>
