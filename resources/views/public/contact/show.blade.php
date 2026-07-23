@extends('layouts.public')

@section('title', 'Liên hệ — '.config('app.name', 'vieclam88'))
@section('meta_description', 'Liên hệ trực tiếp cơ sở vieclam88 qua điện thoại hoặc Zalo để được hỗ trợ về việc làm đang tuyển.')
@section('canonical', route('contact.show'))

@section('content')
<div class="container">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Liên hệ</li>
        </ol>
    </nav>

    <div class="text-center mb-4">
        <h1 class="h2">Liên hệ vieclam88</h1>
        <p class="text-secondary mb-0">
            Chọn cơ sở phù hợp để gọi điện hoặc nhắn Zalo. Trang này không thu thập hay lưu yêu cầu tư vấn.
        </p>
    </div>

    @if ($branches->isEmpty())
        <div class="alert alert-light border text-center py-5">
            Hiện chưa có kênh liên hệ công khai. Bạn có thể xem các
            <a href="{{ route('companies.index') }}">công ty đang tuyển</a>.
        </div>
    @else
        <div class="row g-3">
            @foreach ($branches as $branch)
                <div class="col-12 col-md-6">
                    <article class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">{{ $branch->name }}</h2>
                            <p class="text-secondary">
                                {{ $branch->address_detail ? $branch->address_detail.', ' : '' }}{{ $branch->administrativeUnit->name }}
                            </p>
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                @if ($branch->phone)
                                    <a href="tel:{{ $branch->phone }}" class="btn btn-primary" style="min-height:48px">
                                        Gọi {{ $branch->phone }}
                                    </a>
                                @endif
                                @if ($branch->zalo)
                                    <a href="https://zalo.me/{{ $branch->zalo }}" class="btn btn-outline-primary" style="min-height:48px" target="_blank" rel="noopener">
                                        Nhắn Zalo
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card border-0 bg-primary bg-opacity-10 mt-4">
        <div class="card-body text-center p-4">
            <h2 class="h5">Tìm theo doanh nghiệp</h2>
            <p>Truy cập hồ sơ công khai của công ty để xem đúng các vị trí còn tuyển.</p>
            <a href="{{ route('companies.index') }}" class="btn btn-primary" style="min-height:48px">Xem danh sách công ty</a>
        </div>
    </div>
</div>
@endsection
