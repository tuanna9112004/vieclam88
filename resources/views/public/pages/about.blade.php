@extends('layouts.public')

@section('title', $page->meta_title ?? $page->title.' — '.config('app.name', 'vieclam88'))
@section('meta_description', $page->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($page->content), 160))
@section('canonical', route('pages.about'))

@section('content')
<div class="container">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
        </ol>
    </nav>

    <article>
        <h1 class="h2 mb-4">{{ $page->title }}</h1>
        <div class="content">{!! nl2br(e($page->content)) !!}</div>
    </article>
</div>
@endsection
