<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Việc làm — {{ config('app.name', 'vieclam88') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h4 mb-0">Việc làm</h1>
                <a href="{{ route('hr.jobs.create') }}" class="btn btn-primary">Thêm việc làm</a>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <table class="table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên vị trí</th>
                        <th>Công ty</th>
                        <th>Cơ sở phụ trách</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr>
                            <td>{{ $job->code }}</td>
                            <td>{{ $job->title }}</td>
                            <td>{{ $job->company?->name }}</td>
                            <td>{{ $job->ownerBranch?->name }}</td>
                            <td>{{ $job->status }}</td>
                            <td class="text-end">
                                @can('update', $job)
                                    <a href="{{ route('hr.jobs.edit', $job) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $jobs->links() }}
        </div>
    </body>
</html>
