@extends('manager.layouts.app')

@section('title', 'Modifier Promotion')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Modifier la promotion</h1>
            <p class="mt-1 text-sm text-gray-700">{{ $promotion->name }}</p>
        </div>
        <a href="{{ route('manager.promotions.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Retour</a>
    </div>

    @include('manager.promotions._form', [
        'action' => route('manager.promotions.update', $promotion),
        'method' => 'PUT',
        'submitLabel' => 'Mettre a jour',
    ])
</div>
@endsection
