@extends('layouts.admin')

@section('meta-title', __('Product Categories'))

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-dark">
            <div class="card-header">
                <h5 class="d-inline-block mt-1">{{ __('All product categories') }}</h5>
                <a href="{{ route('admin.product-category.create') }}"
                    class="btn btn-outline-info float-right btn-sm">{{ __('Add new') }}</a>
            </div>
            <div class="card-body">
                <div class="btn-group mb-3">
                    <a href="{{ route('admin.product-category.index') . '?type=all' }}"
                        class="btn btn-outline-dark {{ request()->get('type') == 'all' ? 'active' : ''  }}">{{ __('All') }}</a>
                    <a href="{{ route('admin.product-category.index') }}"
                        class="btn btn-outline-dark {{ request()->has('type') ? '' : 'active'  }}">{{ __('Active') }}</a>
                    <a href="{{ route('admin.product-category.index') . '?type=trash' }}"
                        class="btn btn-outline-dark {{ request()->get('type') == 'trash' ? 'active' : ''  }}">{{ __('Trashed') }}</a>
                </div>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Thumbnail</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productCategories as $productCategory)
                        <tr>
                            <td>
                                @if($productCategory->thumbnail)
                                <img height="40" src="{{ asset($productCategory->thumbnail) }}"
                                    alt="{{ $productCategory->name }}">
                                @endif
                            </td>
                            <td>{{ $productCategory->name }}</td>
                            <td>{{ $productCategory->slug }}</td>
                            <td><span
                                    class="badge badge-@if($productCategory->status == true){{ 'success' }} @else{{ 'warning' }} @endif">{{ $productCategory->status_text }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.product-category.show', $productCategory->id) }}"
                                    class="btn btn-outline-success btn-sm"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('admin.product-category.edit', $productCategory->id) }}"
                                    class="btn btn-outline-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.product-category.destroy', $productCategory->id) }}"
                                    class="d-inline-block" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i
                                            class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    {{ $productCategories->links() }}
                </table>
            </div>
        </div>
    </div>
</div>
@endsection