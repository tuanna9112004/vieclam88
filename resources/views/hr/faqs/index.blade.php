@extends('layouts.hr')

@section('title', 'Câu hỏi thường gặp')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Câu hỏi thường gặp</h1>
        <a href="{{ route('hr.faqs.create') }}" class="btn btn-primary" style="min-height:48px">Thêm câu hỏi</a>
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
                    <th>Thứ tự</th>
                    <th>Câu hỏi</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($faqs as $faq)
                    <tr>
                        <td>{{ $faq->sort_order }}</td>
                        <td>{{ $faq->question }}</td>
                        <td>{{ $faq->is_active ? 'Hiển thị' : 'Ẩn' }}</td>
                        <td class="text-end">
                            <a href="{{ route('hr.faqs.edit', $faq) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>

                            <form method="POST" action="{{ route('hr.faqs.destroy', $faq) }}" class="d-inline"
                                onsubmit="return confirm('Xóa câu hỏi này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $faqs->links() }}
@endsection
