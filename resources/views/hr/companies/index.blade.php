<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Công ty — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h4 mb-0">Công ty</h1>
                <a href="{{ route('hr.companies.create') }}" class="btn btn-primary">Thêm công ty</a>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if (session('duplicate_warning'))
                <div class="alert alert-warning">{{ session('duplicate_warning') }}</div>
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
                        <th>Tên</th>
                        <th>Ngành nghề</th>
                        <th>Website</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($companies as $company)
                        <tr>
                            <td>{{ $company->name }}</td>
                            <td>{{ $company->industry }}</td>
                            <td>{{ $company->website }}</td>
                            <td class="text-end">
                                <a href="{{ route('hr.company-locations.index', $company) }}" class="btn btn-sm btn-outline-secondary">Địa điểm</a>
                                <a href="{{ route('hr.company-contacts.index', $company) }}" class="btn btn-sm btn-outline-secondary">Đầu mối</a>
                                <a href="{{ route('hr.companies.edit', $company) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>

                                @can('delete', $company)
                                    <form method="POST" action="{{ route('hr.companies.destroy', $company) }}" class="d-inline">
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

            {{ $companies->links() }}

            @if ($trashedCompanies->isNotEmpty())
                <h2 class="h6 mt-5 mb-3">Công ty đã xóa</h2>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trashedCompanies as $company)
                            <tr>
                                <td>{{ $company->name }}</td>
                                <td class="text-end">
                                    @can('restore', $company)
                                        <form method="POST" action="{{ route('hr.companies.restore', $company) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">Khôi phục</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </body>
</html>
